<?php

namespace Tests\Unit\Features\Webmail;

use Tests\TestCase;

class RoundcubePluginTest extends TestCase
{
    public function test_fidoma_plugin_keeps_the_old_project_interactions(): void
    {
        $plugin = file_get_contents(resource_path('roundcube/fidoma.php'));

        $this->assertIsString($plugin);
        $this->assertStringContainsString("add_hook('startup'", $plugin);
        $this->assertStringContainsString("add_hook('authenticate'", $plugin);
        $this->assertStringContainsString("add_hook('user_create'", $plugin);
        $this->assertStringContainsString("add_hook('write_log'", $plugin);
        $this->assertStringContainsString("add_hook('template_object_logout'", $plugin);
        $this->assertStringContainsString("add_hook('render_page'", $plugin);
        $this->assertStringContainsString("add_hook('logout_after'", $plugin);
        $this->assertStringContainsString("register_action('plugin.fidoma.decryptzip'", $plugin);
        $this->assertStringContainsString("add_hook('template_object_messageattachments'", $plugin);
        $this->assertStringContainsString('request_security_check(rcube_utils::INPUT_GET)', $plugin);
        $this->assertStringContainsString('app(EncryptedZipArchive::class)', $plugin);
        $this->assertStringContainsString("hash_equals(\$context['binding']", $plugin);
        $this->assertStringContainsString("\$rcmail->login(\$context['username']", $plugin);
        $this->assertStringContainsString("\$_SESSION['fidoma_binding']", $plugin);
        $this->assertStringContainsString("\$context['archive_rule']['password']", $plugin);
    }
}
