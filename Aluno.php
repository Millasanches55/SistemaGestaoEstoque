<?php
require_once 'Pessoa.php';

class Aluno extends Pessoa {
    public $curso;

    public function __construct($nome) {
        parent::__construct($nome);
    }

    public function exibirDados() {
        return "Aluno(a): {$this->nome}";
    }
}
?>