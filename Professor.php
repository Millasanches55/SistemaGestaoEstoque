<?php
class Professor {
    private $nome;
    private $cargo;

    public function __construct($nome, $cargo) {
        $this->nome = $nome;
        $this->cargo = $cargo;
    }

    public function exibirProfessor() {
        return $this->cargo . ": " . htmlspecialchars($this->nome);
    }
}
?>



