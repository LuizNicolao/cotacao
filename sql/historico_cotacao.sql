CREATE TABLE IF NOT EXISTS `historico_cotacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cotacao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `acao` varchar(50) NOT NULL,
  `motivo` text DEFAULT NULL,
  `data_acao` timestamp NOT NULL DEFAULT current_timestamp(),
  `itens_aprovados` json DEFAULT NULL,
  `tipo_aprovacao` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cotacao_id` (`cotacao_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `historico_cotacao_ibfk_1` FOREIGN KEY (`cotacao_id`) REFERENCES `cotacoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historico_cotacao_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 