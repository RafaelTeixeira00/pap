<?php

function app_default_gym_settings()
{
    return [
        'gym_name' => 'PowerLift Gym',
        'contact_email' => 'PowerLiftGym0@gmail.com',
        'contact_phone' => '',
        'contact_address' => '',
        'price_base' => '',
        'price_power' => '',
        'price_personal' => '',
    ];
}

function app_gym_settings($allowDefaultsWhenUnconfigured = false)
{
    static $settings = null;
    if (is_array($settings)) {
        return $settings;
    }

    $configPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'pap-private-data'
        . DIRECTORY_SEPARATOR . 'auth-config.php';
    if (!is_file($configPath) && $allowDefaultsWhenUnconfigured) {
        return app_default_gym_settings();
    }

    $loadedSettings = app_default_gym_settings();

    $statement = app_database()->query(
        'SELECT setting_key, setting_value FROM gym_settings'
    );
    foreach ($statement->fetchAll() as $row) {
        if (array_key_exists($row['setting_key'], $loadedSettings)) {
            $loadedSettings[$row['setting_key']] = $row['setting_value'];
        }
    }

    $settings = $loadedSettings;
    return $settings;
}

function save_gym_settings(array $values)
{
    $allowedKeys = [
        'contact_email',
        'contact_phone',
        'contact_address',
        'price_base',
        'price_power',
        'price_personal',
    ];
    $connection = app_database();
    $statement = $connection->prepare(
        'INSERT INTO gym_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value) '
        . 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );

    $connection->beginTransaction();
    try {
        foreach ($allowedKeys as $key) {
            $statement->execute([
                'setting_key' => $key,
                'setting_value' => $values[$key],
            ]);
        }
        $connection->commit();
    } catch (PDOException $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}
