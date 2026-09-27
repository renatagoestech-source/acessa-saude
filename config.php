<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'conecta_saude';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $port = getenv('ACESSA_DB_PORT') ?: DB_PORT;
    $host = getenv('ACESSA_DB_HOST') ?: DB_HOST;
    $user = getenv('ACESSA_DB_USER') ?: DB_USER;
    $pass = getenv('ACESSA_DB_PASS');
    if ($pass === false) $pass = DB_PASS;
    $dbName = getenv('ACESSA_DB_NAME') ?: DB_NAME;
    $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbName . ';charset=utf8mb4';

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    ensure_schema_compatibility($pdo, $dbName);

    return $pdo;
}

function ensure_schema_compatibility(PDO $pdo, string $dbName): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $quotedDb = $pdo->quote($dbName);
    $columnExists = static function (string $table, string $column) use ($pdo, $quotedDb): bool {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '.$quotedDb.' AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    };
    $addColumn = static function (string $table, string $column, string $definition) use ($pdo, $columnExists): void {
        if (!$columnExists($table, $column)) $pdo->exec('ALTER TABLE `'.$table.'` ADD COLUMN `'.$column.'` '.$definition);
    };

    $addColumn('pacientes', 'email', 'VARCHAR(180) NULL AFTER telefone');
    $addColumn('profissionais', 'slug', 'VARCHAR(160) NULL AFTER email');
    $addColumn('profissionais', 'cnpj', 'VARCHAR(30) NULL AFTER slug');
    $addColumn('profissionais', 'horario_funcionamento', 'VARCHAR(180) NULL AFTER logo_arquivo');
    $addColumn('profissionais', 'aviso_publico', 'TEXT NULL AFTER horario_funcionamento');
    $addColumn('profissionais', 'mensagem_pos_venda', 'TEXT NULL AFTER aviso_publico');
    try { $pdo->exec("UPDATE profissionais SET slug = CONCAT('profissional-', id) WHERE slug IS NULL OR slug = ''"); $pdo->exec("ALTER TABLE profissionais MODIFY slug VARCHAR(160) NOT NULL UNIQUE"); } catch (Throwable $ignored) {}
    $addColumn('consultas', 'assunto', 'TEXT NULL AFTER especialidade');
    $addColumn('consultas', 'cancelamento_motivo', 'VARCHAR(255) NULL AFTER status');
    $addColumn('exames_resultados', 'enviado_por', 'INT UNSIGNED NULL AFTER anexo_arquivo');
    $addColumn('suporte_mensagens', 'protocolo', 'VARCHAR(30) NULL AFTER id');
    $addColumn('suporte_mensagens', 'resposta', 'TEXT NULL AFTER mensagem');
    $addColumn('suporte_mensagens', 'atualizado_em', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER criado_em');
    try {
        $pdo->exec("UPDATE suporte_mensagens SET protocolo = CONCAT('SUP-LEGACY-', LPAD(id, 10, '0')) WHERE protocolo IS NULL OR protocolo = ''");
        $pdo->exec('ALTER TABLE suporte_mensagens MODIFY protocolo VARCHAR(30) NOT NULL');
        $indexes = $pdo->query("SHOW INDEX FROM suporte_mensagens WHERE Key_name = 'uq_suporte_protocolo'")->fetchAll();
        if (!$indexes) $pdo->exec('ALTER TABLE suporte_mensagens ADD UNIQUE KEY uq_suporte_protocolo (protocolo)');
    } catch (Throwable $ignored) {}

    $pdo->exec('CREATE TABLE IF NOT EXISTS lista_espera (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, paciente_id INT UNSIGNED NOT NULL, ubs_id VARCHAR(20) NOT NULL, especialidade VARCHAR(120) NOT NULL, data_consulta DATE NOT NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_lista_espera (paciente_id,ubs_id,especialidade,data_consulta)) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS auditoria (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id INT UNSIGNED NULL, tipo_usuario VARCHAR(30) NULL, acao VARCHAR(100) NOT NULL, entidade VARCHAR(80) NULL, entidade_id VARCHAR(80) NULL, detalhes TEXT NULL, ip VARCHAR(45) NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_auditoria_data (criado_em)) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracao_app (id TINYINT UNSIGNED PRIMARY KEY, modo ENUM('ubs','profissional') NOT NULL DEFAULT 'ubs', nome_exibicao VARCHAR(150) NOT NULL DEFAULT 'Acessa+ Saúde', especialidade VARCHAR(150) NULL, registro_profissional VARCHAR(100) NULL, telefone VARCHAR(40) NULL, whatsapp VARCHAR(40) NULL, endereco VARCHAR(255) NULL, modalidade VARCHAR(100) NULL, valor_consulta DECIMAL(10,2) NULL, apresentacao TEXT NULL, logo_arquivo VARCHAR(255) NULL, horario_funcionamento VARCHAR(180) NULL, aviso_publico TEXT NULL, cor_primaria VARCHAR(20) NOT NULL DEFAULT '#0fa7a7', cor_secundaria VARCHAR(20) NOT NULL DEFAULT '#075b5d', atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB");
    $pdo->exec('INSERT IGNORE INTO configuracao_app (id) VALUES (1)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS profissionais (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(150) NOT NULL, email VARCHAR(180) NOT NULL UNIQUE, slug VARCHAR(160) NOT NULL UNIQUE, cnpj VARCHAR(30) NULL, senha_hash VARCHAR(255) NOT NULL, especialidade VARCHAR(150) NULL, registro_profissional VARCHAR(100) NULL, telefone VARCHAR(40) NULL, whatsapp VARCHAR(40) NULL, endereco VARCHAR(255) NULL, modalidade VARCHAR(100) NULL, valor_consulta DECIMAL(10,2) NULL, apresentacao TEXT NULL, logo_arquivo VARCHAR(255) NULL, cor_primaria VARCHAR(20) NOT NULL DEFAULT '#0fa7a7', cor_secundaria VARCHAR(20) NOT NULL DEFAULT '#075b5d', status ENUM('ativo','suspenso','pendente') NOT NULL DEFAULT 'ativo', criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB");
    $addColumn('profissionais', 'mensagem_pos_venda', 'TEXT NULL AFTER aviso_publico');
    $pdo->exec("CREATE TABLE IF NOT EXISTS profissional_pacientes (profissional_id INT UNSIGNED NOT NULL, paciente_id INT UNSIGNED NOT NULL, consentimento_em DATETIME NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (profissional_id,paciente_id)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS agendas_profissionais (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, profissional_id INT UNSIGNED NOT NULL, dia_semana TINYINT UNSIGNED NOT NULL, inicio TIME NOT NULL, fim TIME NOT NULL, duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 30, ativo TINYINT(1) NOT NULL DEFAULT 1, UNIQUE KEY uq_agenda_profissional (profissional_id,dia_semana,inicio,fim)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS consultas_profissionais (id VARCHAR(60) PRIMARY KEY, profissional_id INT UNSIGNED NOT NULL, paciente_id INT UNSIGNED NOT NULL, data_consulta DATE NOT NULL, horario TIME NOT NULL, assunto TEXT NULL, valor DECIMAL(10,2) NULL, forma_pagamento VARCHAR(40) NULL, pagamento_status ENUM('pendente','pago','dispensado') NOT NULL DEFAULT 'pendente', pago_em DATETIME NULL, status ENUM('solicitada','agendada','confirmada','atendida','cancelada','faltou') NOT NULL DEFAULT 'solicitada', cancelamento_motivo VARCHAR(255) NULL, confirmada_em DATETIME NULL, atendida_em DATETIME NULL, criada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_cp_prof_data (profissional_id,data_consulta,horario), INDEX idx_cp_paciente (paciente_id,data_consulta)) ENGINE=InnoDB");
    $addColumn('consultas_profissionais', 'forma_pagamento', 'VARCHAR(40) NULL AFTER valor');
    $addColumn('consultas_profissionais', 'pagamento_status', "ENUM('pendente','pago','dispensado') NOT NULL DEFAULT 'pendente' AFTER forma_pagamento");
    $addColumn('consultas_profissionais', 'pago_em', 'DATETIME NULL AFTER pagamento_status');
    $addColumn('consultas_profissionais', 'confirmada_em', 'DATETIME NULL AFTER cancelamento_motivo');
    $addColumn('consultas_profissionais', 'atendida_em', 'DATETIME NULL AFTER confirmada_em');
    $pdo->exec("CREATE TABLE IF NOT EXISTS prontuarios_profissionais (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, profissional_id INT UNSIGNED NOT NULL, paciente_id INT UNSIGNED NOT NULL, consulta_id VARCHAR(60) NULL, tipo VARCHAR(80) NOT NULL DEFAULT 'evolucao', conteudo MEDIUMTEXT NOT NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_prontuario_paciente (profissional_id,paciente_id,criado_em)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS planos_assinatura (id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, codigo VARCHAR(40) NOT NULL UNIQUE, nome VARCHAR(100) NOT NULL, valor_mensal DECIMAL(10,2) NOT NULL, limite_pacientes INT UNSIGNED NULL, ativo TINYINT(1) NOT NULL DEFAULT 1) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS assinaturas_profissionais (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, profissional_id INT UNSIGNED NOT NULL, plano_id SMALLINT UNSIGNED NOT NULL, status ENUM('pendente','ativa','inadimplente','cancelada','expirada') NOT NULL DEFAULT 'pendente', inicio DATE NULL, fim DATE NULL, gateway VARCHAR(40) NULL, referencia_externa VARCHAR(180) NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_assinatura_prof (profissional_id,status)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS pagamentos_profissionais (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, profissional_id INT UNSIGNED NOT NULL, assinatura_id BIGINT UNSIGNED NULL, valor DECIMAL(10,2) NOT NULL, status ENUM('pendente','aprovado','recusado','estornado','manual') NOT NULL DEFAULT 'pendente', metodo VARCHAR(40) NULL, gateway VARCHAR(40) NULL, referencia_externa VARCHAR(180) NULL, pago_em DATETIME NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_pagamento_prof (profissional_id,status)) ENGINE=InnoDB");
    $pdo->exec("INSERT INTO planos_assinatura (codigo,nome,valor_mensal,limite_pacientes) VALUES ('essencial','Plano 50 pacientes',49.99,50),('profissional','Plano 100 pacientes',99.99,100) ON DUPLICATE KEY UPDATE nome=VALUES(nome),valor_mensal=VALUES(valor_mensal),limite_pacientes=VALUES(limite_pacientes),ativo=1");
    $pdo->exec("UPDATE planos_assinatura SET ativo=0 WHERE codigo NOT IN ('essencial','profissional')");
}
