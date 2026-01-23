<?php

/**
 * Minimal DB helper using Propel connection available in AtoM/Symfony 1.
 */
class AhgTranslationDb
{
    public static function conn()
    {
        // Propel is used by AtoM (Qubit). This returns a PDO-like connection.
        return Propel::getConnection();
    }

    public static function fetchOne(string $sql, array $params = array())
    {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function fetchAll(string $sql, array $params = array())
    {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function exec(string $sql, array $params = array()): int
    {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->rowCount();
    }

    public static function lastInsertId(): string
    {
        return (string)self::conn()->lastInsertId();
    }
}
