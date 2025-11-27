<?php
/**
 * Painel de Administração de Licenças - AI SEO PRO
 * 
 * HOSPEDE ESTE ARQUIVO NO SEU SERVIDOR PRIVADO
 * Acesse via: https://seu-dominio.com/admin/license-admin.php
 * 
 * IMPORTANTE: Proteja este arquivo com senha ou .htaccess
 */

session_start();

// ================================================================
// CONFIGURAÇÕES - EDITE AQUI
// ================================================================

// Senha para acessar o painel (MUDE ISSO!)
define('ADMIN_PASSWORD', 'sua_senha_super_secreta_aqui');

// Sua chave do Asaas
define('ASAAS_API_KEY', '$aact_COLOQUE_SUA_CHAVE_AQUI');

// Modo sandbox (true para testes, false para produção)
define('ASAAS_SANDBOX', false);

// Arquivo para armazenar licenças (ou use banco de dados)
define('LICENSES_FILE', __DIR__ . '/licenses.json');

// ================================================================
// NÃO MODIFIQUE ABAIXO DESTA LINHA
// ================================================================

$api_url = ASAAS_SANDBOX 
    ? 'https://sandbox.asaas.com/api/v3/'
    : 'https://api.asaas.com/v3/';

// Autenticação simples
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['authenticated'] = true;
        } else {
            $login_error = 'Senha incorreta!';
        }
    }
    
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        showLoginForm($login_error ?? '');
        exit;
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Carrega licenças
function loadLicenses() {
    if (file_exists(LICENSES_FILE)) {
        return json_decode(file_get_contents(LICENSES_FILE), true) ?: [];
    }
    return [];
}

// Salva licenças
function saveLicenses($licenses) {
    file_put_contents(LICENSES_FILE, json_encode($licenses, JSON_PRETTY_PRINT));
}

// Gera chave de licença
function generateLicenseKey($prefix = 'AISEO') {
    $segments = [];
    for ($i = 0; $i < 4; $i++) {
        $segments[] = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
    }
    return $prefix . '-' . implode('-', $segments);
}

// Processa ações
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'generate_license':
            $licenses = loadLicenses();
            $type = $_POST['license_type'] ?? 'standard';
            $days = intval($_POST['days'] ?? 365);
            $note = $_POST['note'] ?? '';
            
            $prefix = $type === 'master' ? 'MASTER' : ($type === 'gift' ? 'GIFT' : 'AISEO');
            $key = generateLicenseKey($prefix);
            
            $licenses[$key] = [
                'key' => $key,
                'type' => $type,
                'status' => 'active',
                'days' => $type === 'master' ? 0 : $days, // 0 = sem expiração
                'note' => $note,
                'created_at' => date('Y-m-d H:i:s'),
                'activated_at' => null,
                'activated_site' => null,
            ];
            
            saveLicenses($licenses);
            $message = "Licença gerada: <strong>$key</strong>";
            $messageType = 'success';
            break;
            
        case 'revoke_license':
            $licenses = loadLicenses();
            $key = $_POST['license_key'] ?? '';
            
            if (isset($licenses[$key])) {
                $licenses[$key]['status'] = 'revoked';
                saveLicenses($licenses);
                $message = "Licença revogada: $key";
                $messageType = 'success';
            }
            break;
            
        case 'delete_license':
            $licenses = loadLicenses();
            $key = $_POST['license_key'] ?? '';
            
            if (isset($licenses[$key])) {
                unset($licenses[$key]);
                saveLicenses($licenses);
                $message = "Licença excluída: $key";
                $messageType = 'success';
            }
            break;
    }
}

// Carrega dados
$licenses = loadLicenses();

// Busca pagamentos do Asaas
function getAsaasPayments() {
    global $api_url;
    
    $ch = curl_init($api_url . 'payments?limit=20');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'access_token: ' . ASAAS_API_KEY,
        ],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return $data['data'] ?? [];
}

$payments = [];
if (strpos(ASAAS_API_KEY, 'COLOQUE') === false) {
    $payments = getAsaasPayments();
}

// Renderiza página
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - AI SEO PRO Licenças</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; }
        .header a { color: #fff; text-decoration: none; opacity: 0.8; }
        .header a:hover { opacity: 1; }
        .card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card h2 { margin-bottom: 15px; font-size: 18px; color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { border-color: #667eea; outline: none; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.2s; }
        .btn-primary { background: #667eea; color: #fff; }
        .btn-primary:hover { background: #5a6fd6; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-danger:hover { background: #c82333; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; font-size: 13px; }
        td { font-size: 13px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .license-key { font-family: monospace; background: #f5f5f5; padding: 3px 6px; border-radius: 4px; font-size: 12px; }
        .copy-btn { background: none; border: none; cursor: pointer; font-size: 14px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab { padding: 10px 20px; background: #e9ecef; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .tab.active { background: #667eea; color: #fff; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat { background: #fff; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat .number { font-size: 32px; font-weight: bold; color: #667eea; }
        .stat .label { font-size: 13px; color: #666; margin-top: 5px; }
        @media (max-width: 768px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
            .form-row { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 AI SEO PRO - Painel de Licenças</h1>
            <a href="?logout=1">Sair</a>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <!-- Estatísticas -->
        <div class="stats">
            <div class="stat">
                <div class="number"><?php echo count($licenses); ?></div>
                <div class="label">Total de Licenças</div>
            </div>
            <div class="stat">
                <div class="number"><?php echo count(array_filter($licenses, fn($l) => $l['status'] === 'active')); ?></div>
                <div class="label">Ativas</div>
            </div>
            <div class="stat">
                <div class="number"><?php echo count(array_filter($licenses, fn($l) => $l['type'] === 'master')); ?></div>
                <div class="label">Master</div>
            </div>
            <div class="stat">
                <div class="number"><?php echo count($payments); ?></div>
                <div class="label">Pagamentos Recentes</div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="showTab('generate')">➕ Gerar Licença</div>
            <div class="tab" onclick="showTab('licenses')">📋 Licenças</div>
            <div class="tab" onclick="showTab('payments')">💰 Pagamentos</div>
            <div class="tab" onclick="showTab('settings')">⚙️ Configurações</div>
        </div>
        
        <!-- Tab: Gerar Licença -->
        <div id="tab-generate" class="tab-content active">
            <div class="card">
                <h2>➕ Gerar Nova Licença</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="generate_license">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tipo de Licença</label>
                            <select name="license_type">
                                <option value="standard">🎫 Padrão (com expiração)</option>
                                <option value="master">👑 Master (sem expiração)</option>
                                <option value="gift">🎁 Presente (para amigos)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Validade (dias)</label>
                            <select name="days">
                                <option value="30">30 dias</option>
                                <option value="90">90 dias</option>
                                <option value="180">180 dias</option>
                                <option value="365" selected>1 ano</option>
                                <option value="730">2 anos</option>
                                <option value="0">Vitalício</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Observação (opcional)</label>
                        <input type="text" name="note" placeholder="Ex: Licença para João da Silva">
                    </div>
                    <button type="submit" class="btn btn-primary">🔑 Gerar Licença</button>
                </form>
            </div>
        </div>
        
        <!-- Tab: Licenças -->
        <div id="tab-licenses" class="tab-content">
            <div class="card">
                <h2>📋 Todas as Licenças</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Chave</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Validade</th>
                            <th>Observação</th>
                            <th>Criada em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($licenses)): ?>
                        <tr><td colspan="7" style="text-align:center; color:#666;">Nenhuma licença gerada ainda.</td></tr>
                        <?php else: ?>
                        <?php foreach (array_reverse($licenses) as $license): ?>
                        <tr>
                            <td>
                                <span class="license-key"><?php echo htmlspecialchars($license['key']); ?></span>
                                <button class="copy-btn" onclick="copyToClipboard('<?php echo $license['key']; ?>')" title="Copiar">📋</button>
                            </td>
                            <td>
                                <?php
                                $typeLabels = ['master' => '👑 Master', 'gift' => '🎁 Presente', 'standard' => '🎫 Padrão'];
                                echo $typeLabels[$license['type']] ?? $license['type'];
                                ?>
                            </td>
                            <td>
                                <?php
                                $statusBadges = [
                                    'active' => '<span class="badge badge-success">Ativa</span>',
                                    'used' => '<span class="badge badge-info">Em uso</span>',
                                    'revoked' => '<span class="badge badge-danger">Revogada</span>',
                                    'expired' => '<span class="badge badge-warning">Expirada</span>',
                                ];
                                echo $statusBadges[$license['status']] ?? $license['status'];
                                ?>
                            </td>
                            <td><?php echo $license['days'] == 0 ? '♾️ Vitalício' : $license['days'] . ' dias'; ?></td>
                            <td><?php echo htmlspecialchars($license['note'] ?? '-'); ?></td>
                            <td><?php echo $license['created_at']; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="revoke_license">
                                    <input type="hidden" name="license_key" value="<?php echo $license['key']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Revogar esta licença?')">Revogar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab: Pagamentos -->
        <div id="tab-payments" class="tab-content">
            <div class="card">
                <h2>💰 Pagamentos Recentes (Asaas)</h2>
                <?php if (strpos(ASAAS_API_KEY, 'COLOQUE') !== false): ?>
                <p style="color:#666;">Configure sua chave do Asaas para ver os pagamentos.</p>
                <?php elseif (empty($payments)): ?>
                <p style="color:#666;">Nenhum pagamento encontrado.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><code><?php echo $payment['id']; ?></code></td>
                            <td><?php echo $payment['customer']; ?></td>
                            <td>R$ <?php echo number_format($payment['value'], 2, ',', '.'); ?></td>
                            <td>
                                <?php
                                $pStatusBadges = [
                                    'PENDING' => '<span class="badge badge-warning">Pendente</span>',
                                    'RECEIVED' => '<span class="badge badge-success">Pago</span>',
                                    'CONFIRMED' => '<span class="badge badge-success">Confirmado</span>',
                                    'OVERDUE' => '<span class="badge badge-danger">Vencido</span>',
                                ];
                                echo $pStatusBadges[$payment['status']] ?? $payment['status'];
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($payment['dateCreated'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tab: Configurações -->
        <div id="tab-settings" class="tab-content">
            <div class="card">
                <h2>⚙️ Configurações</h2>
                <div class="form-group">
                    <label>URL do Proxy (para o plugin FREE)</label>
                    <input type="text" readonly value="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/payment-proxy.php'; ?>">
                    <p style="font-size:12px; color:#666; margin-top:5px;">Configure esta URL no arquivo class-upgrade.php do plugin FREE</p>
                </div>
                
                <div class="form-group" style="margin-top:20px;">
                    <label>Chaves Mestre (para wp-config.php)</label>
                    <?php
                    $masterKeys = array_filter($licenses, fn($l) => $l['type'] === 'master' && $l['status'] === 'active');
                    $masterKeysStr = implode(',', array_column($masterKeys, 'key'));
                    ?>
                    <textarea readonly rows="3" style="font-family:monospace;">define('AI_SEO_RM_MASTER_KEYS', '<?php echo $masterKeysStr; ?>');</textarea>
                    <p style="font-size:12px; color:#666; margin-top:5px;">Cole isso no wp-config.php do seu WordPress</p>
                </div>
                
                <div class="form-group" style="margin-top:20px;">
                    <label>Status da API Asaas</label>
                    <p>
                        <?php if (strpos(ASAAS_API_KEY, 'COLOQUE') !== false): ?>
                        <span class="badge badge-warning">⚠️ Não configurada</span>
                        <?php else: ?>
                        <span class="badge badge-success">✅ Configurada</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function showTab(tabId) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelector('.tab[onclick*="' + tabId + '"]').classList.add('active');
        document.getElementById('tab-' + tabId).classList.add('active');
    }
    
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Chave copiada: ' + text);
        });
    }
    </script>
</body>
</html>
<?php

function showLoginForm($error = '') {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AI SEO PRO Admin</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 400px; width: 90%; }
        .login-box h1 { margin: 0 0 20px 0; text-align: center; font-size: 24px; }
        .login-box input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; margin-bottom: 15px; box-sizing: border-box; }
        .login-box button { width: 100%; padding: 12px; background: #667eea; color: #fff; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; }
        .login-box button:hover { background: #5a6fd6; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔐 AI SEO PRO Admin</h1>
        <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Senha de administrador" required autofocus>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
<?php
}
?>
