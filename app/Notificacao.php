<?php

class Notificacao 
{
	public $matricula;
	public $nome;
	public $cpf;
	public $nis;
	public $data_nascimento;
	public $codigo;
	public $ativo;
	public $rejeicoes = array();
	public $protocolo;
	
	public function __construct($db) 
	{
		$this->db = $db;
	}
	
	public function delete($matriculasDelete) {
		$sql = "delete from notificacao where matricula in(" . implode(',', $matriculasDelete) . ");";
		return $this->db->exec($sql);
	}
	
	public function saveLote($notificacoes) {
		$sql = "insert into notificacao (matricula, nome, cpf, nis, data_nascimento, ativo, campo, mensagem, orientacao, protocolo) values ";
		foreach($notificacoes as $value) {
			foreach($value->rejeicoes as $rejeicao) {
				$sql .= "($value->matricula, '$value->nome', '$value->cpf', '$value->nis', '$value->data_nascimento', 'S', '$rejeicao->campo', '$rejeicao->mensagem', '$rejeicao->orientacao', '$value->protocolo'),";
			}
		}
		$sql = substr_replace($sql, ';', -1);
		return $this->db->exec($sql);
	}
	
	public function get($protocolo) {
		$smtp = $this->db->prepare("select * from notificacao where protocolo= :protocolo");
		$smtp->bindValue(":protocolo", $protocolo);
		$smtp->execute();
		$notificacoes = array();
		while($row = $smtp->fetch(PDO::FETCH_OBJ)) {
			$notificacoes[] = $row;
		}
		
		return $notificacoes;
	}
}