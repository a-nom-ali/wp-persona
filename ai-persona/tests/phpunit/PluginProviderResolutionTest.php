<?php

use Ai_Persona\Plugin;
use Ai_Persona\Providers\Ollama_Provider;
use Ai_Persona\Providers\Provider_Interface;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/class-ai-persona.php';

class PluginProviderResolutionTest extends TestCase {

    protected function setUp(): void {
        ai_persona_tests_reset_filters();
        ai_persona_tests_reset_options();
        ai_persona_tests_set_option( 'ai_persona_provider', 'ollama' );
    }

    public function test_existing_provider_is_preserved() {
        $custom_provider = new class() implements Provider_Interface {
            public function generate( $prompt, array $context = array() ) {
                return array( 'output' => 'custom', 'provider' => 'custom' );
            }

            public function stream( $prompt, array $context = array(), ?callable $emit = null ) {
                if ( $emit ) {
                    $emit( array( 'type' => 'done' ) );
                }
            }
        };

        $plugin  = Plugin::instance();
        $resolved = $plugin->resolve_provider( $custom_provider );

        $this->assertSame( $custom_provider, $resolved );
    }

    public function test_default_provider_uses_option_values() {
        ai_persona_tests_set_option( 'ai_persona_provider_base_url', 'http://example.test:8080/subdir/' );
        ai_persona_tests_set_option( 'ai_persona_provider_model', 'custom-model' );

        $plugin   = Plugin::instance();
        $resolved = $plugin->resolve_provider( null );

        $this->assertInstanceOf( Ollama_Provider::class, $resolved );

        $reflected = new ReflectionClass( $resolved );

        $base_url = $reflected->getProperty( 'base_url' );
        $base_url->setAccessible( true );

        $model = $reflected->getProperty( 'model' );
        $model->setAccessible( true );

        $this->assertSame( 'http://example.test:8080/subdir', $base_url->getValue( $resolved ) );
        $this->assertSame( 'custom-model', $model->getValue( $resolved ) );
    }

    public function test_openai_provider_is_returned_when_selected() {
        ai_persona_tests_set_option( 'ai_persona_provider', 'openai' );
        ai_persona_tests_set_option( 'ai_persona_provider_model', '' );
        ai_persona_tests_set_option( 'ai_persona_provider_base_url', '' );
        ai_persona_tests_set_option( 'ai_persona_api_key', 'test-key' );

        $plugin   = Plugin::instance();
        $resolved = $plugin->resolve_provider( null );

        $this->assertInstanceOf( \Ai_Persona\Providers\OpenAI_Provider::class, $resolved );

        $reflected = new ReflectionClass( $resolved );

        $model = $reflected->getProperty( 'model' );
        $model->setAccessible( true );

        $base = $reflected->getProperty( 'base_url' );
        $base->setAccessible( true );

        $this->assertSame( 'gpt-4o-mini', $model->getValue( $resolved ) );
        $this->assertSame( 'https://api.openai.com/v1', $base->getValue( $resolved ) );
    }

    public function test_anthropic_provider_is_returned_when_selected() {
        ai_persona_tests_set_option( 'ai_persona_provider', 'anthropic' );
        ai_persona_tests_set_option( 'ai_persona_provider_model', '' );
        ai_persona_tests_set_option( 'ai_persona_provider_base_url', '' );
        ai_persona_tests_set_option( 'ai_persona_api_key', 'anth-key' );

        $plugin   = Plugin::instance();
        $resolved = $plugin->resolve_provider( null );

        $this->assertInstanceOf( \Ai_Persona\Providers\Anthropic_Provider::class, $resolved );

        $reflected = new ReflectionClass( $resolved );

        $model = $reflected->getProperty( 'model' );
        $model->setAccessible( true );

        $base = $reflected->getProperty( 'base_url' );
        $base->setAccessible( true );

        $this->assertSame( 'claude-3-haiku-20240307', $model->getValue( $resolved ) );
        $this->assertSame( 'https://api.anthropic.com/v1', $base->getValue( $resolved ) );
    }
}
