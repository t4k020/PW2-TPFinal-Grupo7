<?php

class EditorController
{
    private $model;
    private $renderer;
    private $request;
    private $preguntaModel;
    private $usuarioModel;
    private $partidaModel;
    private $categoriaModel;

    public function __construct($model, $renderer, $request, $preguntaModel, $usuarioModel, $partidaModel, $categoriaModel)
    {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->preguntaModel = $preguntaModel;
        $this->usuarioModel = $usuarioModel;
        $this->partidaModel = $partidaModel;
        $this->categoriaModel = $categoriaModel;
    }

    //aca hay que mover solo las funciones correspondientes al editor
    public function mostrar()
    {

    }

    public function verCategorias()
    {
        $this->verificarEditor();
        $id = $_POST['id'] ?? null;
        $categoria = $this->categoriaModel->getCategoria($id);
        $categorias = $this->preguntaModel->getCategorias();//Quiza deberia moverse a CategoriaModel
        $hubo_categorias = (isset($categorias) || $categorias > 0);

        $this->renderer->render("verCategorias",
            ["categorias" => $categorias,
                "categoria" => $categoria,
                "hubo_categorias" => $hubo_categorias]);
    }

    public function guardarCategoria() {
        $id = $_POST['id'] ?? null;
        $nombre = $_POST['nombre'];
        $color = $_POST['color'];

        if(!isset($id))
            $this->categoriaModel->guardarCategoria($nombre, $color);
        else {
            $id = intval($id);
            $this->categoriaModel->updateCategoria($id, $nombre, $color);
        }
        header("location: verCategorias");
        exit();
    }

    public function eliminarCategoria() {
        $idCategoria = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $this->categoriaModel->borrarCategoria($idCategoria);
        header("Location: verCategorias");
        exit();
    }
    private function verificarEditor()
    {
    }
}