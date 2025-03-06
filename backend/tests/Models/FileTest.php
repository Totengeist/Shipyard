<?php

namespace Tests\Models;

use Laracasts\TestDummy\Factory;
use Shipyard\Models\File;
use Tests\TestCase;

class FileModelTest extends TestCase {
    /**
     * @return void
     */
    public function testCanCreateFile() {
        $file1 = Factory::create('Shipyard\Models\File');

        /** @var File $file2 */
        $file2 = File::query()->findOrFail($file1->id);
        $this->assertEquals($file1->filename, $file2->filename);
    }

    /**
     * @return void
     */
    public function testCanDeleteFile() {
        $file = \Shipyard\FileManager::moveUploadedFile(\Tests\APITestCase::createSampleUpload('Battle.space'));

        $this->assertEquals($file->id, File::query()->findOrFail($file->id)->id);
        $this->assertTrue(file_exists($file->getFilePath()));

        $file->delete();

        $this->assertFalse(file_exists($file->getFilePath()));
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        File::query()->findOrFail($file->id);
    }

    /**
     * @return void
     */
    public function testCanGetFileItem() {
        $ship = Factory::create('Shipyard\Models\Ship');
        $save = Factory::create('Shipyard\Models\Save');
        $mod = Factory::create('Shipyard\Models\Modification');
        $screen = Factory::create('Shipyard\Models\Screenshot');
        $thumb = Factory::create('Shipyard\Models\Thumbnail', ['screenshot_id' => $screen->id]);

        $screen->thumbnails->add($thumb);

        $file0 = Factory::create('Shipyard\Models\File');
        $file1 = $ship->file;
        $file2 = $save->file;
        $file3 = $mod->file;
        $file4 = $screen->file;
        $file5 = $thumb->file;

        $this->assertEquals(null, $file0->item());
        $this->assertEquals($ship->id, $file1->item()->id);
        $this->assertEquals('Shipyard\Models\Ship', get_class($file1->item()));
        $this->assertEquals($save->id, $file2->item()->id);
        $this->assertEquals('Shipyard\Models\Save', get_class($file2->item()));
        $this->assertEquals($mod->id, $file3->item()->id);
        $this->assertEquals('Shipyard\Models\Modification', get_class($file3->item()));
        $this->assertEquals($screen->id, $file4->item()->id);
        $this->assertEquals('Shipyard\Models\Screenshot', get_class($file4->item()));
        $this->assertEquals($thumb->id, $file5->item()->id);
        $this->assertEquals('Shipyard\Models\Thumbnail', get_class($file5->item()));
    }
}
