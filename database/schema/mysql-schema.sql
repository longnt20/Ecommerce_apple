-- Laravel MySQL baseline, reviewed from the 2026-10-28 schema.
-- Schema and migration metadata only; no business data or database selection.
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE `attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `banner_product` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `banner_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banner_product_product_id_foreign` (`product_id`),
  KEY `banner_product_banner_id_foreign` (`banner_id`),
  CONSTRAINT `banner_product_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`) ON DELETE CASCADE,
  CONSTRAINT `banner_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `banners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `redirect_url` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '',
  `order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `cart_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price_at_add` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `carts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carts_user_id_unique` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `parent_id` bigint DEFAULT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `coupon_uses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `coupon_id` bigint unsigned NOT NULL,
  `status` enum('used','unused','expired') NOT NULL DEFAULT 'unused',
  `applied_at` date DEFAULT NULL,
  `expired_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `coupon_uses_user_id_foreign` (`user_id`),
  KEY `coupon_uses_coupon_id_foreign` (`coupon_id`),
  CONSTRAINT `coupon_uses_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_uses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'fixed',
  `discount_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_max_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `start_date` date DEFAULT NULL,
  `expire_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `max_usage` int DEFAULT NULL,
  `used_count` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`),
  KEY `coupons_user_id_foreign` (`user_id`),
  CONSTRAINT `coupons_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `frames` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `top_background` varchar(255) DEFAULT NULL,
  `bottom_background` varchar(255) DEFAULT NULL,
  `ribbon_image` varchar(255) DEFAULT NULL,
  `left_decor_image` varchar(255) DEFAULT NULL,
  `right_decor_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `inventory` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `available_quantity` int NOT NULL DEFAULT '0',
  `reserved_quantity` int NOT NULL DEFAULT '0',
  `incoming_quantity` int NOT NULL DEFAULT '0',
  `min_stock_level` int NOT NULL DEFAULT '0',
  `max_stock_level` int DEFAULT NULL,
  `reorder_point` int NOT NULL DEFAULT '0',
  `reorder_quantity` int NOT NULL DEFAULT '0',
  `location` varchar(255) DEFAULT NULL,
  `bin_code` varchar(255) DEFAULT NULL,
  `last_counted_at` timestamp NULL DEFAULT NULL,
  `last_received_at` timestamp NULL DEFAULT NULL,
  `last_shipped_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_warehouse_id_product_variant_id_unique` (`warehouse_id`,`product_variant_id`),
  KEY `inventory_product_variant_id_foreign` (`product_variant_id`),
  KEY `inventory_warehouse_id_product_variant_id_index` (`warehouse_id`,`product_variant_id`),
  KEY `inventory_available_quantity_index` (`available_quantity`),
  KEY `inventory_quantity_index` (`quantity`),
  CONSTRAINT `inventory_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `inventory_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `inventory_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_code` varchar(255) NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned NOT NULL,
  `from_warehouse_id` bigint unsigned DEFAULT NULL,
  `to_warehouse_id` bigint unsigned DEFAULT NULL,
  `type` enum('import','export','transfer','adjust','return','damage','reserve','commit','release','inventory') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `quantity` int NOT NULL,
  `before_quantity` int NOT NULL,
  `after_quantity` int NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `reference_code` varchar(255) DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text,
  `meta_data` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','completed','cancelled') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_transactions_transaction_code_unique` (`transaction_code`),
  KEY `inventory_transactions_product_variant_id_foreign` (`product_variant_id`),
  KEY `inventory_transactions_from_warehouse_id_foreign` (`from_warehouse_id`),
  KEY `inventory_transactions_to_warehouse_id_foreign` (`to_warehouse_id`),
  KEY `inventory_transactions_created_by_foreign` (`created_by`),
  KEY `inventory_transactions_approved_by_foreign` (`approved_by`),
  KEY `inventory_transactions_warehouse_id_product_variant_id_index` (`warehouse_id`,`product_variant_id`),
  KEY `inventory_transactions_type_status_index` (`type`,`status`),
  KEY `inventory_transactions_transaction_code_index` (`transaction_code`),
  KEY `inventory_transactions_created_at_index` (`created_at`),
  CONSTRAINT `inventory_transactions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `inventory_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `inventory_transactions_from_warehouse_id_foreign` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `inventory_transactions_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `inventory_transactions_to_warehouse_id_foreign` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `inventory_transactions_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `variant_id` bigint unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `variant_name` varchar(255) DEFAULT NULL,
  `quantity` int NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_variant_id_foreign` (`variant_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL DEFAULT '1',
  `code` varchar(255) NOT NULL,
  `total_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `shipping_fee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `final_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('COD','VNPAY','MOMO') NOT NULL DEFAULT 'COD',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `status` enum('pending','confirmed','shipping','completed','cancelled') NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `ward` varchar(255) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `note` text,
  `paid_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_code_unique` (`code`),
  KEY `orders_user_id_foreign` (`user_id`),
  KEY `orders_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `content` text,
  `thumbnail` varchar(255) DEFAULT NULL,
  `status` enum('draft','pending','published','private','scheduled') NOT NULL DEFAULT 'draft',
  `views` int NOT NULL DEFAULT '0',
  `is_hot` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_slug_unique` (`slug`),
  KEY `posts_user_id_foreign` (`user_id`),
  KEY `posts_category_id_foreign` (`category_id`),
  FULLTEXT KEY `posts_title_description_content_fulltext` (`title`,`description`,`content`),
  CONSTRAINT `posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `product_attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `hex_code` varchar(7) DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_attributes_type_is_active_index` (`type`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `product_specs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `spec_name` varchar(255) NOT NULL,
  `spec_value` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_specs_product_id_foreign` (`product_id`),
  CONSTRAINT `product_specs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `product_variants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `sku` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,2) DEFAULT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `storage` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_variants_sku_unique` (`sku`),
  KEY `product_variants_product_id_foreign` (`product_id`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `short_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `description` text,
  `category_id` bigint unsigned NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `gallery` json DEFAULT NULL,
  `default_price` decimal(15,2) DEFAULT NULL,
  `visibility` enum('public','hidden') NOT NULL DEFAULT 'public',
  `status` enum('published','draft') NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `products_category_id_foreign` (`category_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `promotion_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `item_type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotion_items_promotion_id_foreign` (`promotion_id`),
  KEY `promotion_items_item_id_item_type_index` (`item_id`,`item_type`),
  CONSTRAINT `promotion_items_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `promotions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `thumbnail` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `frame_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotions_slug_unique` (`slug`),
  KEY `promotions_category_id_foreign` (`category_id`),
  KEY `promotions_frame_id_foreign` (`frame_id`),
  CONSTRAINT `promotions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `promotions_frame_id_foreign` FOREIGN KEY (`frame_id`) REFERENCES `frames` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `review_experiences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint unsigned NOT NULL,
  `criterion` varchar(255) NOT NULL COMMENT 'Tên tiêu chí, ví dụ: Chất liệu vải',
  `rating` tinyint NOT NULL COMMENT 'Số sao cho tiêu chí này',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_experiences_review_id_foreign` (`review_id`),
  CONSTRAINT `review_experiences_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `review_media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint unsigned NOT NULL,
  `url` varchar(255) NOT NULL COMMENT 'Đường dẫn ảnh hoặc video',
  `type` enum('image','video') NOT NULL DEFAULT 'image',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_media_review_id_foreign` (`review_id`),
  CONSTRAINT `review_media_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned DEFAULT NULL,
  `rating` tinyint NOT NULL COMMENT '1-5 sao',
  `content` text COMMENT 'Nội dung đánh giá',
  `tags` json DEFAULT NULL COMMENT 'Các tiêu chí được chọn: ["Form áo vừa vặn", "Màu sắc tươi sáng"]',
  `status` enum('public','hidden') NOT NULL DEFAULT 'public' COMMENT 'Trạng thái hiển thị',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_user_id_foreign` (`user_id`),
  KEY `reviews_product_id_foreign` (`product_id`),
  KEY `reviews_product_variant_id_foreign` (`product_variant_id`),
  CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `taggables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` bigint unsigned NOT NULL,
  `taggable_type` varchar(255) NOT NULL,
  `taggable_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `taggables_tag_id_foreign` (`tag_id`),
  KEY `taggables_taggable_type_taggable_id_index` (`taggable_type`,`taggable_id`),
  CONSTRAINT `taggables_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `telescope_entries` (
  `sequence` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `batch_id` char(36) NOT NULL,
  `family_hash` varchar(255) DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT '1',
  `type` varchar(20) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence`),
  UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  KEY `telescope_entries_batch_id_index` (`batch_id`),
  KEY `telescope_entries_family_hash_index` (`family_hash`),
  KEY `telescope_entries_created_at_index` (`created_at`),
  KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` char(36) NOT NULL,
  `tag` varchar(255) NOT NULL,
  PRIMARY KEY (`entry_uuid`,`tag`),
  KEY `telescope_entries_tags_tag_index` (`tag`),
  CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `telescope_monitoring` (
  `tag` varchar(255) NOT NULL,
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','blocked') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'main',
  `address` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `district` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `manager_phone` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_code_unique` (`code`),
  KEY `warehouses_code_is_active_index` (`code`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `wishlists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wishlists_user_id_product_variant_id_unique` (`user_id`,`product_variant_id`),
  KEY `wishlists_product_id_foreign` (`product_id`),
  KEY `wishlists_product_variant_id_foreign` (`product_variant_id`),
  CONSTRAINT `wishlists_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlists_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4, '2025_08_09_053512_create_categories_table', 2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5, '2025_08_11_153758_add_deleted_at_to_categories_table', 3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6, '2025_08_14_065731_create_products_table', 4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7, '2025_08_22_103459_create_product_specs_table', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8, '2025_09_05_082511_add_deleted_at_to_products_table', 6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9, '2025_09_08_091556_create_product_variants_table', 7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10, '2025_09_08_091609_create_warehouses_table', 7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11, '2025_09_08_091628_create_inventory_table', 7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12, '2025_09_08_091648_create_inventory_transactions_table', 7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13, '2025_09_11_142127_add_deleted_at_to_product_variants_table', 8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14, '2025_09_14_020934_create_warehouses_table', 9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15, '2025_09_14_020949_create_inventory_table', 9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16, '2025_09_14_021000_create_inventory_transactions_table', 9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17, '2025_09_19_023726_create_attributes_table', 10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18, '2025_09_20_141645_create_product_attributes_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19, '2025_09_21_133422_add_hex_code_to_product_attributes_table', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20, '2025_10_06_091231_create_personal_access_tokens_table', 13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21, '2025_10_06_133920_add_status_to_users_table', 14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22, '2025_10_14_062753_create_telescope_entries_table', 14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23, '2025_10_14_130003_create_promotions_table', 15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24, '2025_10_14_130004_create_promotions_table', 16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25, '2025_10_14_130155_create_promotion_items_table', 17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26, '2025_10_14_130224_create_promotions_table', 18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27, '2025_10_14_130355_create_promotion_items_table', 18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28, '2025_10_14_130455_create_promotion_items_table', 19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29, '2025_10_16_121521_add_is_featured_to_promotions_table', 20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30, '2025_10_22_131904_create_banners_table', 21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31, '2025_10_29_134432_create_posts_table', 22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32, '2025_10_29_135130_create_tags_table', 22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33, '2025_10_29_135150_create_taggables_table', 22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34, '2019_02_08_105647_create_blocks_contents_tables', 23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35, '2021_11_12_153947_remove_blocks_contents_tables', 23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36, '2025_11_26_152747_create_reviews_table', 24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37, '2025_11_26_152829_create_review_media_table', 24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38, '2025_11_26_152846_create_review_experiences_table', 24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39, '2025_11_28_054851_create_carts_table', 25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40, '2025_11_28_055044_create_cart_items_table', 25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41, '2025_11_28_060333_create_orders_table', 26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42, '2025_11_28_060458_create_order_items_table', 26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43, '2025_12_16_080451_add_warehouse_id_to_orders_table', 27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44, '2025_12_18_103447_create_frames_table', 28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45, '2025_12_18_111854_add_frame_id_to_promotions_table', 29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46, '2026_01_05_111942_create_banner_product_table', 30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47, '2026_01_19_143907_create_wishlists_table', 31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48, '2026_01_20_124628_create_coupons_table', 32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49, '2026_01_20_124638_create_coupon_uses_table', 32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50, '2026_01_20_134957_add_max_usage_to_coupons_table', 33);
SET FOREIGN_KEY_CHECKS=1;
