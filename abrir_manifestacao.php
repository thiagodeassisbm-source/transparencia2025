<?php
require_once 'conexao.php';
$page_title = "Abrir Manifestação";
$tipo_selecionado = $_GET['tipo'] ?? ''; // Pega o tipo da URL para pré-selecionar

// Array com todas as opções para o campo "Assunto"
$assuntos_ouvidoria = [
    "Abastecimento", "Acumulação de Cargo Público", "Administração", "Administração e Planejamento", 
    "Agente Público", "Agricultura e Meio Rural", "Agricultura e Produção Orgânica", "Agrotóxicos", 
    "Alimentação Animal", "Alimentação Escolar", "Aposentadoria", "Armazenamento e Comercialização Agropecuária", 
    "Assédio Sexual", "Assédio Moral", "Assessoria Jurídica", "Assistência à Criança e Adolescente", 
    "Assistência ao Idoso", "Assistência ao Portador de Deficiência", "Assistência e Auxílio Estudantil", 
    "Assistência Hospitalar e Ambulatorial", "Assistência Social", "Atendimento Básico", "Auditoria", 
    "Bem Estar Animal", "Benefício e Auxilio", "Biblioteca", "Bolsas", "Brasil Carinhoso", 
    "Certidões e Declarações", "Cidadania", "Combate e Epidemias", "Combustíveis", "Compras", 
    "Comunicação e Marketing Institucional", "Concurso", "Conduta Administrativa", "Conservação do Solo e da Água", 
    "Construção de Escolas", "Convênio", "Cooperativismo e Associativismo Rural", "Covid-19 (Novo Coronavírus)", 
    "Cultura e Arte", "Desastre Ambiental", "Desenvolvimento Sustentável", "E-SIC", "Educação", 
    "Empresa Simples", "Esporte", "Fertilizantes", "Frequência de Servidores", "FUNDEB", "Gestão", 
    "Habitação", "Igualdade Racial", "Iluminação Pública", "Imposto", "Infraestrutura e Manutenção", 
    "Inspeção de Estabelecimento ou Produto", "Laboratórios", "Lazer", "Legislação", 
    "Licenciamento Ambiental", "Licitações", "Matrículas", "Medicamentos e Aparelhos", 
    "MEI- Microempreendedor Individual", "Meio Ambiente", "MPE- Micro e Pequena Empresa", "Obras Públicas", 
    "Ouvidoria", "Outros", "PAC- Programa de Aceleração do Crescimento", "Participação em Eventos", 
    "Patrimônio", "Pecúaria", "Penitenciárias", "PETI - Programa de Erradicação do Trabalho Infantil", 
    "Planejamento e Orçamento", "Planos de Saúde", "Política Agrícola", "Políticas para Mulheres", 
    "Portador de Deficiência", "Preservação e Conservação Ambiental", "Prestação de Contas", "Previdencia", 
    "Processo Seletivo", "Produção Agropecuária", "Programa Bolsa Família", "Programa Brasil Sorridente", 
    "Programa Farmácia Popular", "Programa Luz para Todos", "Programa mais Educação", "Programa Mais Médico", 
    "Programa Minha Casa Minha Vida", "Programa Saúde da Família", "Programa Viver Sem Limite", "PRONAF", 
    "Pronatec", "Publicidade", "Qualidade de Alimentos", "Receita", "Recursos Públicos", "Remédios", 
    "Recursos Hídricos", "Secretaria de Saúde", "Secretaria de Habitação", "Secretaria de Políticas para Mulheres", "Secretaria de Assistência Social e Direitos Humanos", "Secretaria de Cultura", "Secretaria de Esporte e Lazer", "Secretaria de Educação", "Secretaria de Eficiência", "Secretaria de Inovação e Transformação Digital", "Secretaria Municipal de Desenvolvimento, Indústria, Comércio, Agricultura e Serviços", "Secretaria de Engenharia de Trânsito", "Secretaria de Infraestrutura Urbana", "Secretaria de Comunicação", "Secretaria de Administração", "Secretaria da Fazenda", "Secretaria de Governo", "Secretaria de Gestão de Negócios e Parcerias", "Transparência", "Transportes", "Tributos", "Turismo", "Bebidas"
];
// Ordena a lista em ordem alfabética para facilitar a busca
sort($assuntos_ouvidoria);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Ouvidoria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>
<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item"><a href="ouvidoria.php">Ouvidoria</a></li>
                <li class="breadcrumb-item active" aria-current="page">Abrir Manifestação</li>
            </ol>
        </nav>
        <h1>Nova Manifestação</h1>
    </div>
</header>
<div class="container-fluid container-custom-padding">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            <div class="card">
                <div class="card-header"><h4>Formulário de Manifestação</h4></div>
                <div class="card-body">
                    <form action="processar_ouvidoria.php" method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="tipo_manifestacao" class="form-label">Tipo de Manifestação*</label>
                                <select name="tipo_manifestacao" id="tipo_manifestacao" class="form-select" required>
                                    <option value="" <?php if(empty($tipo_selecionado)) echo 'selected';?>>-- Selecione --</option>
                                    <option value="Sugestão" <?php if($tipo_selecionado == 'Sugestão') echo 'selected';?>>Sugestão</option>
                                    <option value="Elogio" <?php if($tipo_selecionado == 'Elogio') echo 'selected';?>>Elogio</option>
                                    <option value="Solicitação" <?php if($tipo_selecionado == 'Solicitação') echo 'selected';?>>Solicitação</option>
                                    <option value="Reclamação" <?php if($tipo_selecionado == 'Reclamação') echo 'selected';?>>Reclamação</option>
                                    <option value="Denúncia" <?php if($tipo_selecionado == 'Denúncia') echo 'selected';?>>Denúncia</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="assunto" class="form-label">Assunto*</label>
                                <select name="assunto" id="assunto" class="form-select" required>
                                    <option value="">-- Selecione o Assunto --</option>
                                    <?php foreach ($assuntos_ouvidoria as $assunto): ?>
                                        <option value="<?php echo htmlspecialchars($assunto); ?>"><?php echo htmlspecialchars($assunto); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="descricao" class="form-label">Descrição da Manifestação*</label>
                                <textarea name="descricao" id="descricao" class="form-control" rows="5" required></textarea>
                            </div>
                            <hr class="my-3">
                            <p class="text-muted small">Informações de contato são opcionais.</p>
                            <div class="col-md-12 mb-3"><label for="nome" class="form-label">Seu Nome</label><input type="text" name="nome_cidadao" id="nome" class="form-control"></div>
                            <div class="col-md-6 mb-3"><label for="email" class="form-label">Seu E-mail</label><input type="email" name="email" id="email" class="form-control"></div>
                            <div class="col-md-6 mb-3"><label for="telefone" class="form-label">Seu Telefone</label><input type="text" name="telefone" id="telefone" class="form-control"></div>
                        </div>
                        <a href="ouvidoria.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Enviar Manifestação</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<footer class="text-center p-3 mt-4"></footer>
</body>
</html>