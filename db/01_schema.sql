-- Схема БД отчёта по сделкам (часть боевой схемы, лишние колонки убраны).
-- Типы и значения по умолчанию совпадают с продом: даты NOT NULL, «пустая» дата = '0000-00-00 00:00:00'.

SET NAMES utf8mb4;
SET sql_mode = '';

CREATE TABLE `tg_block` (
  `tg_block_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(500) NOT NULL,
  `sort` int(11) NOT NULL,
  PRIMARY KEY (`tg_block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Блоки (группы рынков)';

CREATE TABLE `tg_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(500) NOT NULL,
  `sort` int(11) NOT NULL,
  `cpu` varchar(200) NOT NULL,
  `tg_block_id` int(11) NOT NULL,
  PRIMARY KEY (`category_id`),
  KEY `tg_block_id` (`tg_block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Рынки (вкладки отчёта)';

CREATE TABLE `tg_tool` (
  `tool_id` int(11) NOT NULL AUTO_INCREMENT,
  `tiker` varchar(200) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category_id_signal` int(11) NOT NULL,
  `sort` int(11) NOT NULL,
  PRIMARY KEY (`tool_id`),
  KEY `tiker` (`tiker`),
  KEY `category_id_signal` (`category_id_signal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Активы (инструменты)';

CREATE TABLE `tg_signal` (
  `tg_signal_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id_signal` int(11) NOT NULL COMMENT 'Рынок, tg_category.category_id',
  `tool_id` int(11) NOT NULL COMMENT 'Актив, tg_tool.tool_id',
  `diraction` varchar(100) NOT NULL COMMENT 'buy / sell',
  `type` varchar(100) NOT NULL COMMENT 'market / stop / limit',
  `price_open` double NOT NULL COMMENT 'Цена входа',
  `price_close` double NOT NULL COMMENT 'Цена закрытия',
  `stop_loss` double NOT NULL COMMENT 'Стоп сейчас (после тейка может быть перенесён в безубыток)',
  `stop_loss_rash` double NOT NULL COMMENT 'Стоп для расчёта R (исходный)',
  `risk` double NOT NULL DEFAULT 1 COMMENT 'Риск на сделку, %',
  `take_profit` double NOT NULL COMMENT 'Тейк 1 (0 = нет)',
  `take_profit_2` double NOT NULL COMMENT 'Тейк 2 (0 = нет)',
  `take_profit_3` double NOT NULL COMMENT 'Тейк 3 (0 = нет)',
  `date_take_profit` datetime NOT NULL COMMENT 'Когда сработал тейк 1',
  `date_take_profit_2` datetime NOT NULL COMMENT 'Когда сработал тейк 2',
  `date_take_profit_3` datetime NOT NULL COMMENT 'Когда сработал тейк 3',
  `r` varchar(100) NOT NULL COMMENT 'R, вписанный вручную. Пусто = считается автоматически',
  `comment` varchar(3000) NOT NULL,
  `graph_link` varchar(500) NOT NULL,
  `is_published` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_published` datetime DEFAULT NULL,
  `date_closed` datetime NOT NULL,
  `date_deleted` datetime NOT NULL,
  `date_canceled` datetime NOT NULL,
  `is_deleted` int(11) NOT NULL,
  `is_closed` int(11) NOT NULL,
  `is_canceled` int(11) NOT NULL COMMENT 'Отложенный ордер отменён',
  `is_in_work` int(11) NOT NULL COMMENT 'Отложенный ордер сработал (для market всегда 1)',
  `is_in_work_datetime` datetime NOT NULL,
  PRIMARY KEY (`tg_signal_id`),
  KEY `category_id_signal` (`category_id_signal`),
  KEY `tool_id` (`tool_id`),
  KEY `date_closed` (`date_closed`),
  KEY `date_created` (`date_created`),
  KEY `is_closed` (`is_closed`),
  KEY `is_deleted` (`is_deleted`),
  KEY `is_canceled` (`is_canceled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Сделки (сигналы)';
