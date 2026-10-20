<?php
declare(strict_types=1);

final class DatabaseTest extends BlogTestCase
{
    public function testSeedHasNoUsers(): void
    {
        self::assertFalse(has_users($this->pdo()));
        self::assertGreaterThan(0, count(categories($this->pdo())));
        self::assertGreaterThan(0, count(query_news($this->pdo())));
    }

    public function testCrud(): void
    {
        $pdo = $this->pdo();
        $data = validate_news($this->validNews(), category_ids($pdo))['data'];
        self::assertTrue(save_news($pdo, $data));
        $row = query_news($pdo, 'Titulo de prueba')[0];
        self::assertSame('Titulo de prueba', $row['title']);
        $id = (int) $row['id'];
        self::assertSame('Titulo de prueba', get_news($pdo, $id)['title']);
        self::assertTrue(save_news($pdo, ['title' => 'Nuevo'] + $data, $id));
        self::assertSame('Nuevo', get_news($pdo, $id)['title']);
        self::assertFalse(save_news($pdo, $data, 99999));
        self::assertGreaterThan(0, count(list_news_for_admin($pdo)));
        self::assertTrue(delete_news($pdo, $id));
        self::assertFalse(delete_news($pdo, $id));
        self::assertNull(get_news($pdo, $id));
    }

    public function testSearchIsLiteralAndInjectionSafe(): void
    {
        $pdo = $this->pdo();
        self::assertSame([], query_news($pdo, "%' OR 1=1 --"));
        self::assertSame([], query_news($pdo, '%'));
        self::assertSame([], query_news($pdo, '_'));
        self::assertSame([], query_news($pdo, 'zzzz-no-existe'));
        $first = query_news($pdo)[0];
        self::assertNotEmpty(query_news($pdo, '', (int) $first['category_id']));
        self::assertCount(1, query_news($pdo, '', 0, [(int) $first['id']]));
    }

    public function testMetaAndLegacyAdminRetirement(): void
    {
        $pdo = $this->pdo();
        self::assertNull(meta_get($pdo, 'x'));
        meta_set($pdo, 'x', '1');
        meta_set($pdo, 'x', '2');
        self::assertSame('2', meta_get($pdo, 'x'));

        $pdo->prepare('INSERT INTO users(username, password_hash) VALUES(?, ?)')
            ->execute(['admin', password_hash(RETIRED_DEFAULT, PASSWORD_DEFAULT)]);
        self::assertTrue(has_users($pdo));
        $pdo->exec("DELETE FROM meta WHERE name = 'legacy_checked'");
        self::assertTrue(retire_legacy_default_admin($pdo));
        self::assertFalse(has_users($pdo));
        self::assertFalse(retire_legacy_default_admin($pdo));
    }

    public function testLegacyAdminWithChangedPasswordIsKept(): void
    {
        $pdo = $this->pdo();
        $pdo->prepare('INSERT INTO users(username, password_hash) VALUES(?, ?)')
            ->execute(['admin', password_hash('OtraClaveMuyLarga1', PASSWORD_DEFAULT)]);
        $pdo->exec("DELETE FROM meta WHERE name = 'legacy_checked'");
        retire_legacy_default_admin($pdo);
        self::assertTrue(has_users($pdo));
    }

    public function testDatabaseFileIsPrivate(): void
    {
        clearstatcache();
        self::assertSame('0600', substr(sprintf('%o', fileperms($this->dbFile)), -4));
    }
}
