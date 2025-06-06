<?php
class Professor {
    /*Atributos*/
    private $nome;
    private $cargo;

    /*Métodos*/
    public function __construct($nome, $cargo) {
        $this->nome = $nome;
        $this->cargo = $cargo;
    }

    public function exibirProfessor() {
        return "<p><b>" . $this->cargo . "</b>: " . htmlspecialchars($this->nome) . "</p>";
    }
}
?>



