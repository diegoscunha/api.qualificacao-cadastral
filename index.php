<?php
require 'vendor/autoload.php';

Flight::set('flight.log_errors', true);

function parse($dados) {
	$notif = Flight::notificacao(false);
	$notif->matricula = $dados['matricula'];
	$notif->nome = $dados['nome'];
	$notif->cpf = $dados['cpf'];
	$notif->nis = $dados['nis'];
	$notif->data_nascimento = implode("-", array_reverse(explode("/", $dados['data_nascimento']))) ;
	$notif->codigo = $dados['codigo'];
	//$notif->ativo = $dados['ativo'];
	
	foreach($dados['notificacoes'] as $value) {
		$rejeicao = Flight::rejeicao(false);
		$rejeicao->campo = $value['campo'];
		$rejeicao->mensagem = $value['mensagem'];
		$rejeicao->orientacao = $value['orientacao'];
		$notif->rejeicoes[] = $rejeicao;
	}
	
	return $notif;
}

Flight::map('notFound', function(){
    Flight::json(array(
		'code' => 404,
		'error' => 'Recurso nao encontrado'
	), 404);
});

Flight::map('error', function(Exception $ex){
    Flight::json(array(
		'code' => 500,
		'error' => 'Erro no Servidor'
	), 500);
    echo $ex->getTraceAsString();
});

// Register class with constructor parameters
Flight::register('db', 'PDO', array('mysql:host=localhost;dbname=esocial','root','123456'));
Flight::register('notificacao', 'Notificacao', array(Flight::db()));
Flight::register('rejeicao', 'Rejeicao');

/**
* POST
*/
Flight::route('POST /api/notificacao/qualificacao-cadastral', function(){
	$request = Flight::request();
	$notificacoes = array();
	
	$params = $request->data->data;
	
	if(empty($params)) {
		Flight::redirect('notFound');
	}

	$protocolo = date('YmdGis') . rand(0, 10000);
	$matriculasDelete = array();
	foreach($params as $value) {
		$notificacao = parse($value);
		$notificacao->protocolo = $protocolo;
		$notificacoes[] = $notificacao;
		$matriculasDelete[] = $notificacao->matricula;
	}
	
	$notificar = Flight::notificacao();
	if(!$notificar->delete($matriculasDelete)) {
		Flight::redirect('error');
	}	
	$rows = $notificar->saveLote($notificacoes);
	if(!$rows) {
		Flight::redirect('error');
	}
	
	Flight::json(array(
		'registros' => $rows,
		'protocolo' => $protocolo
	), 201);
});

Flight::route('GET /api/notificacao/qualificacao-cadastral/@protocolo:[0-9]+', function($protocolo){
	$notificar = Flight::notificacao();
	$notificacoes = $notificar->get($protocolo);
	
	if(empty($notificacoes)) {
		Flight::redirect('notFound');
	}
	
	Flight::json(array(
		'notificacoes' => $notificacoes,
	), 200);
});

Flight::start();