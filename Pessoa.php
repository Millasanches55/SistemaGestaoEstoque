<?php
class Pessoa
{
    // 7.1 Classes (Métodos e Atributos)
// Definição da classe Pessoa
    /*Atributo*/
    public $nome;


    // 7.1 Método com passagem de parâmetro
    // 8.1 Funções com passagem de parâmetros
    public function __construct($nome)
    {
        // 3.3 Atribuição
        // Uso de this para acessar atributo da classe
        $this->nome = $nome;
    }


    // 7.1 Outro método da classe
    public function exibirNome()
    {
        // 3.4 Comparação (implicitamente ao retornar em fluxos condicionais)
        return $this->nome;
    }

}
?>