-- Referência para o banco hospedado (ex.: u315410518_transp2025)
-- Execute no phpMyAdmin ou cliente MySQL para conferir o schema real.
-- A clonagem em admin/functions_demo.php usa SHOW COLUMNS e nomes reais das colunas.

-- Categorias (menu lateral): não exige coluna "icone" para o portal funcionar
SHOW COLUMNS FROM categorias;

-- Cards da home: o código espera caminho_icone + tipo_icone (ou só icone em schema legado)
SHOW COLUMNS FROM cards_informativos;

-- Se cards_informativos NÃO tiver tipo_icone, rode no servidor (uma vez), após backup:
-- ALTER TABLE cards_informativos
--   ADD COLUMN tipo_icone ENUM('imagem','bootstrap') NOT NULL DEFAULT 'imagem' AFTER caminho_icone;

-- Se faltar caminho_icone mas existir só "icone" (legado), o clone usa a coluna icone automaticamente.
