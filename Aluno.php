<?php
require_once 'Pessoa.php';

class Aluno extends Pessoa {
    public $curso;

    public function __construct($nome, $curso) {
        parent::__construct($nome);
        $this->curso = $curso;
    }

    public function exibirDados() {
        return "Aluno(a): {$this->nome}";
    }
}
?>

