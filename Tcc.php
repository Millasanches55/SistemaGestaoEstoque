<?php
// 9.1 Conexão PDO
$pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8", "root", "");

// 7.1 Classe com métodos e atributos
class Tcc {
    // Atributos públicos (7.1)
    public $codTcc;
    public $titulo;
    public $aluno1;
    public $aluno2;
    public $aluno3;
    public $curso;
    public $orientador;

    // Atributo privado para conexão PDO (encapsulamento)
    private $pdo;

    // Construtor com parâmetro (8.1 Função com passagem de parâmetro)
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Método para salvar um TCC no banco
    public function salvar() {
        // 9.5 Inserção
        $sql = "INSERT INTO Tcc (titulo, aluno1, aluno2, aluno3, curso, orientador) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        // 3.3 Atribuição com 3.4 Comparação implícita no return
        return $stmt->execute([$this->titulo, $this->aluno1, $this->aluno2, $this->aluno3, $this->curso, $this->orientador]);
    }

    // Método para listar todos os TCCs
    public function listarTodos() {
        // 9.2 Leitura e apresentação de registro
        $stmt = $this->pdo->query("SELECT * FROM Tcc");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Método para deletar um TCC pelo código
    public function deletar($codTcc) {
        $stmt = $this->pdo->prepare("DELETE FROM Tcc WHERE codTcc = ?");
        return $stmt->execute([$codTcc]);
    }
}
?>
