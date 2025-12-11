<?php
// processa_form.php

// Configurações básicas
$fotosDir      = __DIR__ . '/fotos';
$docsDir       = __DIR__ . '/documentos';
$csvFile       = __DIR__ . '/dados.csv';

// Garante que as pastas existam
if (!is_dir($fotosDir)) {
    mkdir($fotosDir, 0775, true);
}
if (!is_dir($docsDir)) {
    mkdir($docsDir, 0775, true);
}

// Função para gerar nome de arquivo único
function nomeUnico($dir, $originalName) {
    $ext  = pathinfo($originalName, PATHINFO_EXTENSION);
    $base = pathinfo($originalName, PATHINFO_FILENAME);
    $slug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $base);
    $uniq = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    return $dir . '/' . $slug . '_' . $uniq . ($ext ? '.' . $ext : '');
}

// Upload de múltiplos arquivos
function processarUploads($campo, $destinoDir) {
    $salvos = [];

    if (!isset($_FILES[$campo])) {
        return $salvos;
    }

    $files = $_FILES[$campo];

    // Se for só 1 arquivo, normaliza pra parecer múltiplo
    if (!is_array($files['name'])) {
        $files = [
            'name'     => [$files['name']],
            'type'     => [$files['type']],
            'tmp_name' => [$files['tmp_name']],
            'error'    => [$files['error']],
            'size'     => [$files['size']],
        ];
    }

    $total = count($files['name']);

    for ($i = 0; $i < $total; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK || $files['size'][$i] == 0) {
            continue; // pula arquivos com erro
        }

        $destino = nomeUnico($destinoDir, $files['name'][$i]);
        if (move_uploaded_file($files['tmp_name'][$i], $destino)) {
            // Guarda só o nome relativo para registrar no CSV
            $salvos[] = basename($destino);
        }
    }

    return $salvos;
}

// Processa uploads
$fotosSalvas = processarUploads('fotos_imovel', $fotosDir);
$docsSalvos  = processarUploads('arquivos_interesse', $docsDir);

// Monta linha para o CSV
// Use o mesmo nome dos campos do formulário
$linha = [
    'data_envio'       => date('Y-m-d H:i:s'),
    'imovel'           => $_POST['imovel']           ?? '',
    'estado'           => $_POST['estado']           ?? '',
    'cidade'           => $_POST['cidade']           ?? '',
    'bairro'           => $_POST['bairro']           ?? '',
    'endereco'         => $_POST['endereco']         ?? '',
    'cep'              => $_POST['cep']              ?? '',
    'data_visita'      => $_POST['data_visita']      ?? '',
    'area'             => $_POST['area']             ?? '',
    'obs_imovel'       => $_POST['obs_imovel']       ?? '',
    'nome_corretor'    => $_POST['nome_corretor']    ?? '',
    'contato_corretor' => $_POST['contato_corretor'] ?? '',
    'matricula'        => $_POST['matricula']        ?? '',
    'tipo_acabamento'  => $_POST['tipo_acabamento']  ?? '',
    'situacao'         => $_POST['situacao']         ?? '',
    'obs_fotos'        => $_POST['obs_fotos']        ?? '',
    'desc_arquivos'    => $_POST['desc_arquivos']    ?? '',
    'valor_aluguel'    => $_POST['valor_aluguel']    ?? '',
    // Listas de arquivos salvos, separados por "|"
    'fotos_imovel'     => implode('|', $fotosSalvas),
    'arquivos_interesse' => implode('|', $docsSalvos),
];

// Cria o CSV com cabeçalho se ainda não existir
$novoArquivo = !file_exists($csvFile);
$fp = fopen($csvFile, 'a');

if ($novoArquivo) {
    // Cabeçalho do CSV
    fputcsv($fp, array_keys($linha), ';');
}

// Escreve a linha de dados
fputcsv($fp, array_values($linha), ';');
fclose($fp);

// Redireciona para uma página de obrigado ou volta para o formulário
// Você pode criar um obrigado.html, por exemplo
header('Location: obrigado.html');
exit;
