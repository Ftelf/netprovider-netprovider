<?php
/**
 * Module structure tests.
 *
 * Each `modules/com_xxx/` is expected to contain `xxx.index.php` (entry
 * point) and `xxx.html.php` (renderer). MainFrame::getPath() relies on
 * this convention, so it's worth a structural guard.
 */

class ModuleStructureTest extends TestCase
{
    public static function modules(): array
    {
        $root = realpath(__DIR__ . '/../../../') . '/modules';
        $cases = [];
        foreach (glob($root . '/com_*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);
            $cases[$name] = [$name, $dir];
        }
        return $cases;
    }

    /** @dataProvider modules */
    public function testModuleHasIndexAndHtmlFiles(string $module, string $dir): void
    {
        $base = str_replace('com_', '', $module);
        $this->assertFileExists("$dir/$base.index.php", "$module/$base.index.php should exist");
        $this->assertFileExists("$dir/$base.html.php",  "$module/$base.html.php should exist");
    }

    /** @dataProvider modules */
    public function testMainFrameResolvesModulePath(string $module, string $dir): void
    {
        $mf = new MainFrame($this->db, $module, '', null);
        $path = $mf->getPath();
        $base = str_replace('com_', '', $module);
        $this->assertStringEndsWith("modules/$module/$base.index.php", $path);
    }
}
