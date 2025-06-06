<?php
class Pessoa {
    /*Atributo*/
    public $nome;


    /*Métodos*/
    public function __construct($nome) {
        $this->nome = $nome;
    }

    public function exibirNome() {
        return $this->nome;
    }

}
?>


