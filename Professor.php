<?php
// 7.1 Classes (com métodos e atributos)
class Professor {
    /*Atributos*/
    private $nome;  // Uso de camelCase
    private $cargo; // Uso de camelCase

    /*Métodos*/
    public function __construct($nome, $cargo) {
        // 3.3 Atribuição
        $this->nome = $nome;
        $this->cargo = $cargo;
    }
    // 8.1 Função com passagem de parâmetros (via construtor)
    public function exibirProfessor() {
        // 3.2 String (concatenação com .)
        // 3.4 Comparação implícita ao uso de valores
        // Função retorna HTML com nome e cargo formatados
        return "<p><b>" . $this->cargo . "</b>: " . htmlspecialchars($this->nome) . "</p>";
    }
}
?>



