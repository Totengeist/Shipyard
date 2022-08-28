<?php

class Section {
    public $path = null;
    public $content = null;
    public $sections = [];

    public function __construct($path = null, $content = null, $level = 0, $subfiles = []) {
        if ($path !== null) {
            $this->path = $path;
        }
        if ($content !== null) {
            $this->get_sections($content, $level, $subfiles);
        }
    }

    public function print_section_tree($ignore = []) {
        if (in_array($this->path, $ignore)) {
            return;
        }
        echo $this->path . "\n";
        foreach ($this->sections as $section) {
            if (is_array($section)) {
                foreach ($section as $sec) {
                    $sec->print_section_tree($ignore);
                }
            } else {
                $section->print_section_tree($ignore);
            }
        }
    }

    public function get_sections($structure, $level = 0, $subfiles = []) {
        $content = [];
        $start = -1;
        $key = null;
        for ($i = 0; $i < count($structure); $i++) {
            $line = $structure[$i];
            if (trim($line) == '') {
                continue;
            }
            $pretext = str_repeat('    ', $level);
            if ($start == -1) {
                if (strpos($line, $pretext . 'BEGIN') === 0) {
                    if (strpos($line, 'END') === (strlen($line)-3)) {
                        // section ends in the same line
                        preg_match('/^ *BEGIN ([^ "]*|"[^"]*") +(.*) END *$/i', $line, $matches);
                        if (count($matches) == 0) {
                            echo __LINE__ . $line;
                        }
                        $section_title = trim($matches[1]);
                        preg_match("/^\"\[i ([0-9]+)\]\"$/i", $section_title, $title_check);
                        if ($title_check) {
                            $section_title = (int) $title_check[1];
                        }
                        if (!isset($matches[2])) {
                            $section_content = '';
                        } else {
                            $section_content = trim($matches[2]);
                        }
                        if ($section_content == '') {
                            $this->addSection($section_title, new Section($this->path . '/' . $section_title, []));
                        } else {
                            preg_match_all('/((?<name>[^ "]+|"[^"]+") +(?<value>[^ "]+|"[^"]+"))/i', $section_content, $content_check);
                            $this->addSection($section_title, new Section($this->path . '/' . $section_title, $content_check[0]));
                        }
                    } else {
                        // section continues
                        preg_match('/^ *BEGIN ([^ "]*|"[^"]*") *$/i', $line, $matches);
                        $key = trim($matches[1]);
                        preg_match("/^\"\[i ([0-9]+)\]\"$/i", $key, $title_check);
                        if ($title_check) {
                            $key = (int) $title_check[1];
                        }
                        $start = $i+1;
                    }
                } else {
                    preg_match('/((?<name>[^ "]+|"[^"]+") +(?<value>[^ "]+|"[^"]+"))/', trim($line), $matches);
                    if (count($matches) == 0) {
                        echo __LINE__ . ' ' . $i . ' ' . $line;
                    } else {
                        $content[trim($matches['name'])] = trim($matches['value']);
                    }
                }
            } else {
                // in a section
                if (strpos($line, $pretext . 'END') === 0) {
                    $this->addSection($key, $this->checkSubfile($this->path . '/' . $key, array_slice($structure, $start, $i - $start), $level+1, $subfiles));
                    $start = -1;
                }
            }
        }
        $this->content = $content;
    }

    public function checkSubfile($path, $content, $level, $subfiles) {
        if (in_array($path, array_keys($subfiles))) {
            return new $subfiles[$path]($content, $level, $subfiles);
        }

        return new Section($path, $content, $level, $subfiles);
    }

    public function addSection($key, $object) {
        if (isset($this->sections[$key])) {
            if (gettype($this->sections[$key]) == 'array') {
                $this->sections[$key][] = $object;
            } else {
                $this->sections[$key] = [$this->sections[$key]];
            }
        } else {
            $this->sections[$key] = $object;
        }
    }

    public function section_exists($section_path) {
        $path = explode('/', $section_path);
        $content = $this;
        foreach ($path as $section) {
            if (isset($content->sections[$section])) {
                $content = $content->sections[$section];
            } else {
                return false;
            }
        }

        return true;
    }

    public function get_section($section_path) {
        $path = explode('/', $section_path);
        $content = $this;
        foreach ($path as $section) {
            $content = $content->sections[$section];
        }

        return $content;
    }
}

class ShipFile extends IVFile {
    public $info = [];

    const WEAPONS = ['GatlingGun', 'Cannon', 'Railgun'];
    const ENGINES = ['Engine'];
    const POWER = ['Reactor', 'FusionReactor'];
    const LOGISTICS = ['MiningLaser', 'DroneBay'];
    const THRUSTERS = ['Thrusters'];
    const CELLS = ['Hull', 'Interior', 'Floor', 'Habitation', 'Armour'];
    const CELL_TYPES = ['Storage'];

    public function __construct($structure = null, $level = 0, $subfiles = []) {
        parent::__construct($structure, $level, $subfiles);

        $info = $this->info = $this->get_info();
        ksort($info);
        print_r($info);
        $info['Weapons'] = 0;
        foreach (self::WEAPONS as $weapon) {
            $info['Weapons'] += $info[$weapon];
        }
        $info['PowerGenerators'] = 0;
        foreach (self::POWER as $gen) {
            $info['PowerGenerators'] += $info[$gen];
        }
        $template = 'Your ship is named %s. It has %d weapons and %d engines. Its %d power generators generate %01.2f Mw.';
        echo sprintf($template,
            $info['Name'],
            $info['Weapons'],
            $info['Engines'],
            $info['PowerGenerators'],
            $info['PowerOutput']
        );
    }

    public function get_info() {
        // - Overall size/area
        // - Crew capacity
        // - Max energy amount
        // - Cargo capacity (I.e. amount of zoned space)-
        // - % armour coverage
        // - And then yeah stats on number of weapons/lasers/drone bays
        $info = [];
        $info['Name'] = $this->content['Name'];
        $info['Engines'] = 0;
        $info['PowerOutput'] = 0;
        $info['Mass'] = (float) $this->content['Mass'];
        foreach (self::WEAPONS as $weapon) {
            if (!isset($info[$weapon])) {
                $info[$weapon] = $this->get_object_count($weapon);
            } else {
                $info[$weapon] += $this->get_object_count($weapon);
            }
        }
        foreach (self::ENGINES as $engine) {
            $info['Engines'] += $this->get_object_count($engine);
        }
        foreach (self::POWER as $generator) {
            if (!isset($info[$generator])) {
                $info[$generator] = $this->get_object_count($generator);
            } else {
                $info[$generator] += $this->get_object_count($generator);
            }
            foreach ($this->get_object_content($generator, 'PowerOutput') as $output) {
                $info['PowerOutput'] += (float) $output;
            }
        }
        foreach (self::LOGISTICS as $item) {
            $info[$item] = $this->get_object_count($item);
        }

        $info = array_merge($info, $this->get_cell_info());

        return $info;
    }

    public function get_cell_info() {
        $types = ['.' => []];
        $cells = [];
        foreach ($this->get_section('GridMap/Palette')->sections as $cell) {
            $path = explode('/', $cell->path);
            $name = $path[count($path)-1];
            if ($name == '') {
                $name = '/';
            }
            foreach ($cell->content as $key => $type) {
                if (in_array($key, self::CELLS)) {
                    $types[$name][] = $key;
                } elseif (in_array($key, self::CELL_TYPES)) {
                    $key = 'Storage ' . $type;
                    $types[$name][] = $key;
                } else {
                    $types[$name] = [];
                }
            }
        }
        $typekeys = array_keys($types);
        $cell_width = strlen($typekeys[count($typekeys)-1])+1;

        foreach ($this->get_section('GridMap/Cells')->content as $cell) {
            for ($i = 1; $i < strlen($cell)-2; $i += $cell_width) {
                $char = trim(substr($cell, $i, $cell_width));
                if (in_array($char, array_keys($types))) {
                    foreach ($types[$char] as $type) {
                        if (!isset($cells[$type])) {
                            $cells[$type] = 1;
                        } else {
                            $cells[$type]++;
                        }
                    }
                }
            }
        }
        if (isset($cells['Habitation'])) {
            $cells['HabitationCapacity'] = floor($cells['Habitation']/9);
        }

        return $cells;
    }

    public function get_object_count($label) {
        $count = 0;
        if (!$this->section_exists('Objects')) {
            return 0;
        }
        foreach ($this->sections['Objects']->sections as $object) {
            if ($object->content['Type'] == $label) {
                $count++;
            }
        }

        return $count;
    }

    public function get_object_content($label, $item = null) {
        if (!$this->section_exists('Objects')) {
            return [];
        }
        $content = [];
        foreach ($this->sections['Objects']->sections as $object) {
            if ($object->content['Type'] == $label) {
                if ($item != null) {
                    $content[] = $object->content[$item];
                } else {
                    $content[] = $object->content;
                }
            }
        }

        return $content;
    }
}

class IVFile extends Section {
    public $file = null;

    public function __construct($structure = null, $level = 0, $subfiles = []) {
        if ($structure !== null) {
            $this->evaluate($structure, $level, $subfiles);
        }
    }

    public function evaluate($structure, $level = 0, $subfiles = []) {
        parent::__construct('', $structure, $level, $subfiles);
    }
}

echo "\n\nInvictus";
$ship = preg_split('/\r?\n/', file_get_contents(__DIR__ . '/../../tests/Invictus.ship'));
new ShipFile($ship);

echo "\n\nRAIDEN_MK-2";
$ship = preg_split('/\r?\n/', file_get_contents(__DIR__ . '/../../tests/RAIDEN_MK-2.ship'));
new ShipFile($ship);

echo "\n\nMultiship save";
$ship = preg_split('/\r?\n/', file_get_contents(__DIR__ . '/../../tests/TestMultipleShips.space'));
new IVFile($ship, 0, ['/Layer' => 'ShipFile']);

?>    