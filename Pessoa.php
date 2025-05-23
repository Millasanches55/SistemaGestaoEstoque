<?php
class Pessoa {
    public $nome;

    public function __construct($nome) {
        $this->nome = $nome;
    }


    public function exibirNome() {
        return $this->nome;
    }

}
?>


