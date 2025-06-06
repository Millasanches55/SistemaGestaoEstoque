<?php
require_once 'Pessoa.php';
/*Uso de Herança*/
class Aluno extends Pessoa {
    /*Atributo*/
    public $curso;

    /*Métodos*/
    public function __construct($nome) {
        parent::__construct($nome);
    }

    public function exibirDados() {
        return "Aluno(a): {$this->nome}";
    }
}
?>