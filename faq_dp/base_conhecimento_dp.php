<?php
$titulo_pagina = "Perguntas Frequentes (FAQ)";
$css_pagina = "../css/base_conhecimento_dp.css"; 
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Verificação de permissão
if (!isset($departamento_usuario) || $departamento_usuario != 'Pessoal') {
    header('Location: ../painel.php');
    exit();
}

// ESTRUTURA DE DADOS COMPLETA, ATUALIZADA COM TODAS AS PERGUNTAS DO SEU DOCUMENTO
$faq_data = [
    "Admissão & Contratação" => [
        ['q' => "Quais documentos o funcionário deve apresentar na admissão?", 'a' => "RG, CPF, CTPS, comprovante de residência, certidão de nascimento/casamento, título de eleitor, PIS, carteira de vacinação (em caso de menor), entre outros conforme função."],
        ['q' => "Pode admitir menor de idade? Qual a idade mínima?", 'a' => "Sim, desde que tenha no mínimo 16 anos. A partir dos 14 anos, apenas como menor aprendiz."],
        ['q' => "Qual o prazo para registro na CTPS e no eSocial?", 'a' => "O registro deve ser feito até um dia antes do início das atividades, tanto na CTPS Digital quanto no eSocial."],
        ['q' => "Preciso registrar funcionário com contrato intermitente no eSocial?", 'a' => "Sim. O contrato intermitente deve ser informado no eSocial por meio do evento S-2200, especificando o tipo de contrato."],
        ['q' => "Qual o prazo máximo para contrato de experiência e como funciona a prorrogação?", 'a' => "O contrato de experiência pode durar até 90 dias no total. Pode ser firmado um único contrato ou dois contratos consecutivos, desde que a soma não ultrapasse esse prazo."],
        ['q' => "Quando posso recontratar um funcionário?", 'a' => "A recontratação pode ocorrer após 90 dias do desligamento, se for para a mesma função. Pode haver um novo contrato de experiência se não ultrapassar o limite legal."],
        ['q' => "Quando é obrigatório emitir o Atestado de Saúde Ocupacional (ASO)?", 'a' => "Na admissão, em exames periódicos, na troca de função, no retorno ao trabalho e na demissão, conforme o PCMSO da empresa."],
        ['q' => "Posso contratar sem experiência ou escolaridade mínima?", 'a' => "Sim, desde que isso não vá contra requisitos legais ou convenção coletiva para a função específica."],
        ['q' => "Qual a diferença entre contrato CLT, temporário e terceirizado?", 'a' => "<b>CLT:</b> Vínculo de emprego direto com a empresa.<br><b>Temporário:</b> Contratado por uma empresa de trabalho temporário para atender a uma necessidade transitória.<br><b>Terceirizado:</b> Funcionário de uma empresa terceira que presta serviços para a sua empresa."],
        ['q' => "O que deve constar no contrato de trabalho inicial?", 'a' => "Dados do empregador e empregado, cargo/função, jornada de trabalho, salário, local de trabalho, e cláusulas específicas como prazo do contrato, comissões, etc."],
    ],
    "Remuneração & Benefícios" => [
        ['q' => "Qual a diferença entre Prêmio, Bonificação e Comissão?", 'a' => "<b>Prêmio:</b> Valor eventual, fora do salário, para reconhecer desempenho. Não tem caráter salarial.<br><b>Bonificação:</b> Valor extra que pode ser habitual ou não; se for habitual, integra o salário.<br><b>Comissão:</b> Pagamento sobre vendas ou produção, sempre integra o salário e todos os encargos."],
        ['q' => "A comissão integra o salário para cálculo de férias e 13º?", 'a' => "Sim, a comissão compõe o salário para o cálculo de férias, 13º salário, INSS e FGTS."],
        ['q' => "Posso descontar comissão paga a mais?", 'a' => "Sim, desde que esteja previsto em contrato ou acordo e que o desconto não ultrapasse os limites legais permitidos."],
        ['q' => "O que é sobreaviso e prontidão? Como remunerar?", 'a' => "<b>Sobreaviso:</b> O funcionário fica de plantão em casa, aguardando chamado. Recebe 1/3 do valor da sua hora normal.<br><b>Prontidão:</b> O funcionário fica à disposição no local de trabalho, aguardando ordens. Recebe 2/3 do valor da sua hora normal."],
        ['q' => "Como formalizar e administrar benefícios (vale, saúde, seguro)?", 'a' => "Por meio de políticas internas claras, acordos coletivos e contratos com as empresas fornecedoras. Devem ser registrados na folha de pagamento quando aplicável."],
    ],
    "Jornada & Ponto" => [
        ['q' => "Qual a diferença entre jornada 12x36, 6x1 e 5x2?", 'a' => "<b>12x36:</b> 12 horas de trabalho por 36 horas de descanso.<br><b>6x1:</b> 6 dias de trabalho por 1 dia de descanso.<br><b>5x2:</b> 5 dias de trabalho por 2 dias de descanso."],
        ['q' => "Qual o tempo mínimo de descanso entre jornadas e o intervalo intrajornada?", 'a' => "O descanso entre jornadas (interjornada) deve ser de no mínimo 11 horas. O intervalo durante a jornada (intrajornada) é de no mínimo 1 hora para jornadas acima de 6h (pode ser reduzido para 30 min por acordo) e 15 minutos para jornadas de 4 a 6h."],
        ['q' => "Empresas com quantos funcionários precisam adotar controle de ponto?", 'a' => "A obrigatoriedade do controle de ponto é para empresas com 20 ou mais funcionários."],
        ['q' => "É obrigatório o controle de ponto para home office?", 'a' => "Sim, se houver controle de jornada. A exceção é para funcionários que trabalham exclusivamente por produção ou tarefa, sem controle de horário."],
        ['q' => "Como funciona o banco de horas?", 'a' => "Não é obrigatório. Deve ser formalizado por acordo individual ou coletivo, com compensação das horas em até 6 meses no acordo individual, ou 1 ano no coletivo."],
        ['q' => "Trabalhar em feriados é permitido? Como compensar?", 'a' => "Sim, desde que haja previsão em acordo coletivo. A compensação deve ser feita com uma folga em outro dia ou com o pagamento do dia trabalhado em dobro."],
    ],
    "Folha de Pagamento & Encargos" => [
        ['q' => "Qual a data máxima para pagamento do salário (ex: 5º dia útil)?", 'a' => "O pagamento do salário deve ser efetuado até o 5º dia útil do mês seguinte ao trabalhado."],
        ['q' => "Que rubricas devem constar nos holerites?", 'a' => "Salário base, horas extras, adicionais (noturno, insalubridade, etc.), descontos legais (INSS, IRRF, pensão alimentícia), valor do FGTS, benefícios e o valor líquido a receber."],
        ['q' => "Qual a periodicidade de recolhimento do INSS e FGTS?", 'a' => "<b>INSS:</b> O recolhimento é mensal e deve ser pago até o dia 20 do mês seguinte, via DCTFWeb.<br><b>FGTS:</b> Também mensal, com pagamento até o dia 20 do mês seguinte, via FGTS Digital."],
        ['q' => "Como funciona a substituição da GFIP pelo FGTS Digital?", 'a' => "O FGTS Digital centraliza o recolhimento do FGTS, usando o PIX como meio de pagamento e os dados diretamente do eSocial, substituindo a antiga guia GFIP."],
    ],
    "Férias" => [
        ['q' => "O que é o “período aquisitivo” de férias?", 'a' => "É o período de 12 meses de trabalho que um funcionário precisa completar para ter direito a 30 dias de férias."],
        ['q' => "Qual o prazo para pagamento das férias?", 'a' => "O pagamento das férias, acrescido do terço constitucional, deve ser feito até 2 dias antes do início do período de gozo."],
        ['q' => "É possível vender parte das férias (abono pecuniário)?", 'a' => "Sim, o funcionário pode vender até 1/3 do seu direito de férias, o que corresponde a 10 dias."],
        ['q' => "A empresa pode definir quando o funcionário tira férias?", 'a' => "Sim, a decisão final sobre o período em que as férias serão gozadas é do empregador, que deve comunicar o empregado com 30 dias de antecedência."],
        ['q' => "Como funcionam as férias coletivas?", 'a' => "São férias concedidas simultaneamente a todos os funcionários da empresa ou a determinados setores. Devem ter no mínimo 10 dias, ser comunicadas ao Ministério do Trabalho e sindicatos com 15 dias de antecedência, e o pagamento segue a regra normal de férias."],
    ],
    "Licenças & Afastamentos" => [
        ['q' => "Qual o prazo para entregar atestado médico?", 'a' => "O funcionário deve entregar o atestado ao DP ou gestor o mais rápido possível, preferencialmente no primeiro dia útil após a ausência, para justificar a falta."],
        ['q' => "Quando o funcionário deve ser encaminhado ao INSS?", 'a' => "Quando uma doença ou acidente gera um afastamento superior a 15 dias consecutivos. A partir do 16º dia, a responsabilidade do pagamento passa a ser do INSS."],
        ['q' => "Como registrar licenças-maternidade, paternidade e outros afastamentos?", 'a' => "Todos os afastamentos devem ser registrados por meio de eventos específicos no eSocial (como o S-2230) e com a devida documentação comprobatória arquivada."],
    ],
    "Rescisão" => [
        ['q' => "Quais verbas são pagas na rescisão?", 'a' => "Saldo de salário, férias vencidas e proporcionais + 1/3, 13º salário proporcional, aviso prévio (trabalhado ou indenizado), e a multa de 40% do FGTS em casos de demissão sem justa causa."],
        ['q' => "Qual o prazo para pagamento da rescisão?", 'a' => "O prazo legal para o pagamento de todas as verbas rescisórias é de até 10 dias corridos, contados a partir do término do contrato."],
        ['q' => "É obrigatória a realização de exame demissional?", 'a' => "Sim, o exame demissional é obrigatório e deve ser realizado até a data da homologação da rescisão, conforme o PCMSO da empresa."],
        ['q' => "Qual o limite para descontos na rescisão?", 'a' => "O valor total dos descontos na rescisão, como adiantamentos e empréstimos, não pode ultrapassar o valor de um salário mensal do empregado."],
        ['q' => "Preciso enviar dados ao eSocial no desligamento?", 'a' => "Sim, é obrigatório. Deve-se fazer o fechamento da folha e enviar os eventos de desligamento (S-2299 ou S-2399) no eSocial."],
    ],
    "Estágio & Aprendizagem" => [
        ['q' => "Quem pode ser contratado como aprendiz?", 'a' => "Jovens entre 14 e 24 anos que estejam matriculados e frequentando cursos de aprendizagem. O contrato pode durar até 2 anos."],
        ['q' => "Quais são os direitos trabalhistas do aprendiz?", 'a' => "Salário compatível, FGTS com alíquota de 2%, férias, 13º salário, vale-transporte e registro na CTPS."],
        ['q' => "Quem pode ser contratado como estagiário?", 'a' => "Estudantes do ensino médio, técnico ou superior que estejam frequentando as aulas regularmente."],
        ['q' => "Quais são os direitos do estagiário?", 'a' => "Bolsa-auxílio (se o estágio for remunerado), auxílio-transporte (se houver deslocamento), recesso remunerado de 30 dias após 1 ano e seguro contra acidentes pessoais."],
        ['q' => "O estágio cria vínculo empregatício?", 'a' => "Não, desde que todas as regras da Lei do Estágio (Lei 11.788/08) sejam cumpridas. Caso contrário, pode ser caracterizado como vínculo de emprego."],
    ],
    "Processos Internos & Treinamento" => [
        ['q' => "O DP participa do processo seletivo ou apenas formaliza a admissão?", 'a' => "Depende da estrutura da empresa. Em empresas menores, o DP pode conduzir todo o processo. Em empresas maiores, geralmente atua após a seleção, cuidando da parte documental e admissional."],
        ['q' => "Como estruturar um programa de integração (onboarding)?", 'a' => "Organizar uma agenda de boas-vindas, apresentar as normas internas, políticas da empresa, benefícios, segurança do trabalho, e apresentar o setor e os líderes diretos. Também pode incluir treinamentos técnicos iniciais."],
    ],
    "Casos Específicos & Diversos" => [
        ['q' => "Existem cotas para contratação de PCD (Pessoa com Deficiência)?", 'a' => "Sim, empresas com 100 ou mais funcionários devem reservar de 2% a 5% de suas vagas para PCDs. A recusa baseada na deficiência é ilegal."],
        ['q' => "O que acontece quando um funcionário é preso?", 'a' => "O contrato de trabalho pode ser suspenso durante o período de reclusão. A empresa deve aguardar a decisão judicial para tomar medidas como uma eventual demissão."],
        ['q' => "O pedido de demissão pode ser feito pelo WhatsApp?", 'a' => "Sim, o pedido de demissão é válido se feito por um meio que comprove a manifestação de vontade do empregado, como o WhatsApp, desde que a comunicação seja clara e inequívoca."],
    ],
];
?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo htmlspecialchars($titulo_pagina); ?></h1>
    </div>

    <div class="content-body">
        <div class="faq-container">
            <div class="faq-header">
                <p>Encontre respostas rápidas para as dúvidas mais comuns do Departamento Pessoal.</p>
                <div class="faq-search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="faq-search" placeholder="Digite para buscar uma pergunta...">
                </div>
            </div>

            <?php foreach ($faq_data as $categoria => $perguntas): ?>
                <section class="faq-category">
                    <h2 class="category-title"><?php echo htmlspecialchars($categoria); ?></h2>
                    <div class="faq-list">
                        <?php foreach ($perguntas as $item): ?>
                            <div class="faq-item">
                                <button class="faq-question">
                                    <span><?php echo htmlspecialchars($item['q']); ?></span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                                <div class="faq-answer">
                                    <p><?php echo $item['a']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
             <p id="no-results-message" style="display: none; text-align: center; padding: 20px; color: #64748b;">Nenhum resultado encontrado para sua busca.</p>
        </div>
    </div>
</div>

</div> 

<script>
document.addEventListener('DOMContentLoaded', function () {
    const faqItems = document.querySelectorAll('.faq-item');
    const searchInput = document.getElementById('faq-search');
    const categories = document.querySelectorAll('.faq-category');
    const noResultsMessage = document.getElementById('no-results-message');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.addEventListener('click', () => {
            const currentlyActive = document.querySelector('.faq-item.active');
            if (currentlyActive && currentlyActive !== item) {
                currentlyActive.classList.remove('active');
            }
            item.classList.toggle('active');
        });
    });

    searchInput.addEventListener('keyup', function (e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        let resultsFound = false;

        categories.forEach(category => {
            const questions = category.querySelectorAll('.faq-item');
            let categoryHasVisibleItems = false;
            
            questions.forEach(item => {
                const questionText = item.querySelector('.faq-question span').textContent.toLowerCase();
                const answerText = item.querySelector('.faq-answer p').textContent.toLowerCase();
                
                if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                    item.style.display = 'block';
                    categoryHasVisibleItems = true;
                    resultsFound = true;
                } else {
                    item.style.display = 'none';
                }
            });

            if (categoryHasVisibleItems) {
                category.style.display = 'block';
            } else {
                category.style.display = 'none';
            }
        });
        
        noResultsMessage.style.display = resultsFound ? 'none' : 'block';
    });
});
</script>

</body>
</html>