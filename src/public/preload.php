<?
    try {
        $pdo = new PDO("mysql:host=".$_ENV["MYSQL_HOST"],$_ENV["MYSQL_USER"], $_ENV["MYSQL_PASSWORD"]);
        $st = $pdo->prepare("SHOW DATABASES");
        $st->execute();

        foreach ($st->fetchAll() as $database) {
            if($database["Database"] == $_ENV["MYSQL_DATABASE"]) $pdo->exec("DROP DATABASE ".$_ENV["MYSQL_DATABASE"]);
        }

        $pdo->exec("CREATE DATABASE ".$_ENV["MYSQL_DATABASE"]);
        $pdo->exec("USE ".$_ENV["MYSQL_DATABASE"]);
        createTables($pdo);
        fillTables($pdo);

        header("Location: Task.php");

    } catch (PDOException $th) {
        //throw $th;
        echo $th;
    }

    function createTables($pdo){
        $pdo->exec("CREATE TABLE `".$_ENV['MYSQL_DATABASE']."`.`users` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;");
        $pdo->exec("CREATE TABLE `".$_ENV['MYSQL_DATABASE']."`.`events` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `event_name` VARCHAR(255) NOT NULL , `event_description` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;");
        $pdo->exec("CREATE TABLE `".$_ENV['MYSQL_DATABASE']."`.`event_dates` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `event_id` INT UNSIGNED NOT NULL , `date` DATETIME NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;");
        $pdo->exec("CREATE TABLE `".$_ENV['MYSQL_DATABASE']."`.`tickets_type` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `ticket_name` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;");
        $pdo->exec("CREATE TABLE `".$_ENV['MYSQL_DATABASE']."`.`events_price` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `event_id` INT UNSIGNED NOT NULL , `ticket_type_id` INT UNSIGNED NOT NULL , `price` INT NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;");
        $pdo->exec("CREATE TABLE `".$_ENV['MYSQL_DATABASE']."`.`tickets` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `order_id` INT UNSIGNED NOT NULL , `ticket_type_id` INT UNSIGNED NOT NULL , `barcode` VARCHAR(120) NOT NULL , PRIMARY KEY (`id`), UNIQUE (`barcode`)) ENGINE = InnoDB;");
        $pdo->exec("CREATE TABLE `".$_ENV['MYSQL_DATABASE']."`.`orders` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `user_id` INT UNSIGNED NOT NULL , `event_id` INT UNSIGNED NOT NULL , `event_date_id` INT UNSIGNED NOT NULL , `tickets_quantity` INT NOT NULL , `equal_price` INT NOT NULL , `created` DATETIME NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;");
        
        $pdo->exec("ALTER TABLE `event_dates` ADD FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;");
        $pdo->exec("ALTER TABLE `events_price` ADD FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT; ALTER TABLE `events_price` ADD FOREIGN KEY (`ticket_type_id`) REFERENCES `tickets_type`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;");
        $pdo->exec("ALTER TABLE `orders` ADD FOREIGN KEY (`event_date_id`) REFERENCES `event_dates`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT; ALTER TABLE `orders` ADD FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT; ALTER TABLE `orders` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;");
        $pdo->exec("ALTER TABLE `tickets` ADD FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT; ALTER TABLE `tickets` ADD FOREIGN KEY (`ticket_type_id`) REFERENCES `tickets_type`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;");
    }

    function fillTables($pdo){
        $pdo->exec("INSERT INTO `events` (`id`, `event_name`, `event_description`) VALUES (NULL, 'Event 1', 'Event 1 description'), (NULL, 'Event 2', 'Event 2 description'), (NULL, 'Event 3', 'Event 3 description'), (NULL, 'Event 4', 'Event 4 description')");
        $pdo->exec("INSERT INTO `users` (`id`, `name`) VALUES (NULL, 'Alex'), (NULL, 'Ivan'), (NULL, 'Peter'), (NULL, 'Sandy');");
        $pdo->exec("INSERT INTO `tickets_type` (`id`, `ticket_name`) VALUES (NULL, 'kid'), (NULL, 'adult'), (NULL, 'group'), (NULL, 'preferential');");
        $pdo->exec("INSERT INTO `event_dates` (`id`, `event_id`, `date`) VALUES (NULL, '1', '2024-11-01 13:00:00'), (NULL, '1', '2024-11-01 17:00:00'), (NULL, '2', '2024-11-01 10:00:00'), (NULL, '3', '2024-11-02 22:00:00'), (NULL, '4', '2024-11-05 8:30:00');");
        $pdo->exec("INSERT INTO `events_price` (`id`, `event_id`, `ticket_type_id`, `price`) VALUES (NULL, '1', '1', '250'), (NULL, '1', '2', '500'), (NULL, '1', '3', '450'), (NULL, '1', '4', '300'), (NULL, '2', '1', '200'), (NULL, '2', '2', '500'), (NULL, '3', '2', '700'), (NULL, '3', '4', '500'), (NULL, '4', '2', '600'), (NULL, '4', '3', '500');");
    }