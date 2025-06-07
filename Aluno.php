<?php
require_once 'Pessoa.php'; // Inclusão de classe externa (herança será usada abaixo)
/*Uso de Herança*/
class Aluno extends Pessoa { //  7.3 Uso de Herança | 7.1 Classe com métodos e atributos
    /*Atributo público*/
    public $curso;

    /*Métodos*/
    public function __construct($nome) { // 8.1 Função com passagem de parâmetro
        parent::__construct($nome); // Chamada ao construtor da superclasse
    }

    public function exibirDados() { // Método que retorna string
        return "Aluno(a): {$this->nome}"; // 3.2 String (concatenação com variáveis)
    }
}
?>