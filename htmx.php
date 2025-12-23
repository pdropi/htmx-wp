<?php
/**
 * Plugin Name: WP HTMX Alerts
 * Description: Plugin para integração HTMX com WordPress retornando alerts Bootstrap (alert-success, alert-danger).
 * Version: 0.1
 * Author: Pedro Ramos
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_HTMX_Alerts {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_htmx_news_action', [ $this, 'handle_request' ] );
        add_action( 'wp_ajax_nopriv_htmx_news_action', [ $this, 'handle_request' ] );
        add_shortcode( 'htmx_news_form', [ $this, 'render_form' ] );
    }

    public function enqueue_scripts() {
        // HTMX via CDN (ou local, se preferir)
        wp_enqueue_script(
            'htmx',
            'https://unpkg.com/htmx.org@1.9.12',
            [],
            null,
            true
        );

        wp_localize_script( 'htmx', 'HTMX_NEWS', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'htmx_news_nonce' ),
        ]);
    }

    /**
     * Shortcode do formulário
     */
    public function render_form() {
        ob_start();
        ?>
        <form 
            hx-post="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
            hx-target="#htmx-response"
            hx-swap="innerHTML"
        >
            <input type="hidden" name="action" value="htmx_news_action">
            <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'htmx_news_nonce' ) ); ?>">

            <div class="mb-3">
                <input 
                    type="text" 
                    name="email" 
                    class="form-control" 
                    placeholder="Seu e-mail"
                    required
                >
            </div>

            <button class="btn btn-primary">
                Receber notícias
            </button>
        </form>

        <div id="htmx-response" class="mt-3"></div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handler AJAX
     */
    public function handle_request() {

        if (
            empty( $_POST['nonce'] ) ||
            ! wp_verify_nonce( $_POST['nonce'], 'htmx_news_nonce' )
        ) {
            $this->alert( 'danger', 'Falha de segurança (nonce inválido).' );
        }

        $email = isset( $_POST['email'] )
            ? sanitize_email( $_POST['email'] )
            : '';

        if ( ! is_email( $email ) ) {
            $this->alert( 'danger', 'E-mail inválido.' );
        }

        // Exemplo: salvar como comentário, lead ou option
        // Aqui só simulamos sucesso
        $this->alert(
            'success',
            'Cadastro realizado com sucesso! Você receberá nossas notícias.'
        );
    }

    /**
     * Retorna alerta Bootstrap para HTMX
     */
    private function alert( $type, $message ) {
        wp_send_json_success(
            sprintf(
                '<div class="alert alert-%s" role="alert">%s</div>',
                esc_attr( $type ),
                esc_html( $message )
            )
        );
    }
}

new WP_HTMX_Alerts();
