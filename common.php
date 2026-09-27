<?php
declare(strict_types=1);

function json_response(array $data, int $status = 200): never
{
    // Remove qualquer warning/saída acidental antes do JSON, evitando
    // que o navegador receba uma resposta inválida após um agendamento.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function body_json(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

function required_string(array $data, string $key): string
{
    $value = trim((string)($data[$key] ?? ''));

    if ($value === '') {
        json_response([
            'success' => false,
            'message' => "O campo '{$key}' é obrigatório."
        ], 422);
    }

    return $value;
}

function clean_list(mixed $value): array
{
    if (!is_array($value)) {
        return [];
    }

    $result = [];

    foreach ($value as $item) {
        $item = trim((string)$item);

        if ($item !== '' && !in_array($item, $result, true)) {
            $result[] = $item;
        }
    }

    return $result;
}

function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function is_holiday(string $date): bool
{
    $md = date('m-d', strtotime($date));

    return in_array($md, [
        '01-01',
        '04-21',
        '05-01',
        '09-07',
        '10-12',
        '11-02',
        '11-15',
        '12-25'
    ], true);
}

function is_business_day(string $date): bool
{
    $weekday = (int)date('N', strtotime($date));

    return $weekday <= 5 && !is_holiday($date);
}

function admin_session(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function require_admin(): array
{
    $session = admin_session();

    if (!$session) {
        json_response([
            'success' => false,
            'message' => 'Sessão administrativa expirada. Faça login novamente.'
        ], 401);
    }

    return $session;
}

function professional_session(): ?array
{
    return $_SESSION['professional'] ?? null;
}

function require_professional(): array
{
    $session = professional_session();
    if (!$session) {
        json_response(['success' => false, 'message' => 'Sessão profissional expirada. Faça login novamente.'], 401);
    }
    return $session;
}

function authorize_ubs(string $ubsId): array
{
    $session = require_admin();

    if (
        $session['tipo'] !== 'desenvolvedor' &&
        $session['ubs_id'] !== $ubsId
    ) {
        json_response([
            'success' => false,
            'message' => 'Você não tem permissão para acessar esta UBS.'
        ], 403);
    }

    return $session;
}
