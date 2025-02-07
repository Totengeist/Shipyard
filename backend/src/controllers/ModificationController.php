<?php

namespace Shipyard\Controllers;

class ModificationController extends ItemController {
    public function __construct() {
        parent::__construct();
        $this->modelType = 'Shipyard\Models\Modification';
        $this->modelName = 'Modification';
        $this->modelSlug = 'modification';
    }
}
