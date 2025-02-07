<?php

namespace Shipyard\Controllers;

class SaveController extends ItemController {
    public function __construct() {
        parent::__construct();
        $this->modelType = 'Shipyard\Models\Save';
        $this->modelName = 'Save';
        $this->modelSlug = 'save';
    }
}
