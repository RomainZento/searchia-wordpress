<?php
/**
 * Twenty Twenty-Five functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

// Adds theme support for post formats.
if ( ! function_exists( 'twentytwentyfive_post_format_setup' ) ) :
	/**
	 * Adds theme support for post formats.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_post_format_setup() {
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_post_format_setup' );

// Enqueues editor-style.css in the editors.
if ( ! function_exists( 'twentytwentyfive_editor_style' ) ) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_editor_style() {
		add_editor_style( 'assets/css/editor-style.css' );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_editor_style' );

// Enqueues the theme stylesheet on the front.
if ( ! function_exists( 'twentytwentyfive_enqueue_styles' ) ) :
	/**
	 * Enqueues the theme stylesheet on the front.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_enqueue_styles() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$src    = 'style' . $suffix . '.css';

		wp_enqueue_style(
			'twentytwentyfive-style',
			get_parent_theme_file_uri( $src ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
		wp_style_add_data(
			'twentytwentyfive-style',
			'path',
			get_parent_theme_file_path( $src )
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'twentytwentyfive_enqueue_styles' );

// Registers custom block styles.
if ( ! function_exists( 'twentytwentyfive_block_styles' ) ) :
	/**
	 * Registers custom block styles.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_block_styles() {
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'twentytwentyfive' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_block_styles' );

// Registers pattern categories.
if ( ! function_exists( 'twentytwentyfive_pattern_categories' ) ) :
	/**
	 * Registers pattern categories.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_pattern_categories() {

		register_block_pattern_category(
			'twentytwentyfive_page',
			array(
				'label'       => __( 'Pages', 'twentytwentyfive' ),
				'description' => __( 'A collection of full page layouts.', 'twentytwentyfive' ),
			)
		);

		register_block_pattern_category(
			'twentytwentyfive_post-format',
			array(
				'label'       => __( 'Post formats', 'twentytwentyfive' ),
				'description' => __( 'A collection of post format patterns.', 'twentytwentyfive' ),
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_pattern_categories' );

// Registers block binding sources.
if ( ! function_exists( 'twentytwentyfive_register_block_bindings' ) ) :
	/**
	 * Registers the post format block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_register_block_bindings() {
		register_block_bindings_source(
			'twentytwentyfive/format',
			array(
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'twentytwentyfive' ),
				'get_value_callback' => 'twentytwentyfive_format_binding',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_register_block_bindings' );

// Registers block binding callback function for the post format name.
if ( ! function_exists( 'twentytwentyfive_format_binding' ) ) :
	/**
	 * Callback function for the post format name block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return string|void Post format name, or nothing if the format is 'standard'.
	 */
	function twentytwentyfive_format_binding() {
		$post_format_slug = get_post_format();

		if ( $post_format_slug && 'standard' !== $post_format_slug ) {
			return get_post_format_string( $post_format_slug );
		}
	}
endif;



// Envoyer l'article à l'IA dès qu'il est publié ou mis à jour
add_action('save_post', function($post_id) {
    if (wp_is_post_revision($post_id)) return;
    
    // On demande au rag-engine de ré-indexer
    wp_remote_get('http://rag-engine:8000/ingest'); 
}, 10, 1);


// my part
// Injecter le Chatbot IA dans le pied de page
add_action('wp_footer', function() {
    ?>
    <style>
        #ai-chat-container { position: fixed; bottom: 20px; right: 20px; z-index: 1000; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        #ai-chat-button { width: 60px; height: 60px; background: #21759b; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.3); transition: transform 0.3s; }
        #ai-chat-button:hover { transform: scale(1.1); }
        #ai-chat-window { display: none; width: 350px; height: 500px; background: white; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.2); flex-direction: column; overflow: hidden; margin-bottom: 20px; }
        #ai-header { background: #21759b; color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; }
        #ai-messages { flex: 1; padding: 15px; overflow-y: auto; background: #f7f7f7; display: flex; flex-direction: column; gap: 10px; }
        .msg { padding: 10px 15px; border-radius: 15px; max-width: 80%; font-size: 14px; line-height: 1.4; }
        .msg-user { background: #21759b; color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
        .msg-ai { background: #e2e2e2; color: #333; align-self: flex-start; border-bottom-left-radius: 2px; }
        #ai-input-area { border-top: 1px solid #eee; padding: 10px; display: flex; }
        #ai-input-area input { flex: 1; border: 1px solid #ddd; padding: 10px; border-radius: 20px; outline: none; }
        #ai-send { background: none; border: none; color: #21759b; font-weight: bold; cursor: pointer; margin-left: 10px; }
    </style>

    <div id="ai-chat-container">
        <div id="ai-chat-window">
            <div id="ai-header">Assistant IA <span>●</span></div>
            <div id="ai-messages">
                <div class="msg msg-ai">Bonjour ! Je connais ce site par cœur. Posez-moi une question.</div>
            </div>
            <div id="ai-input-area">
                <input type="text" id="ai-user-query" placeholder="Posez votre question...">
                <button id="ai-send">Envoyer</button>
            </div>
        </div>
        <div id="ai-chat-button">
            <span style="font-size: 30px; color: white;">💬</span>
        </div>
    </div>

    <script>
        const btn = document.getElementById('ai-chat-button');
        const win = document.getElementById('ai-chat-window');
        const send = document.getElementById('ai-send');
        const input = document.getElementById('ai-user-query');
        const msgContainer = document.getElementById('ai-messages');

        btn.onclick = () => { win.style.display = (win.style.display === 'flex') ? 'none' : 'flex'; };

        async function askAI() {
            const query = input.value.trim();
            if (!query) return;

            // Message utilisateur
            msgContainer.innerHTML += `<div class="msg msg-user">${query}</div>`;
            input.value = '';
            msgContainer.scrollTop = msgContainer.scrollHeight;

            // Message de chargement
            const loading = document.createElement('div');
            loading.className = 'msg msg-ai';
            loading.innerText = '...';
            msgContainer.appendChild(loading);

            try {
                // APPEL À TON API DOCKER
                const response = await fetch(`http://localhost:8000/ask?query=${encodeURIComponent(query)}`);
                const data = await response.json();
                loading.innerText = data.answer;
            } catch (e) {
                loading.innerText = "Désolé, je n'arrive pas à joindre mon cerveau local.";
            }
            msgContainer.scrollTop = msgContainer.scrollHeight;
        }

        send.onclick = askAI;
        input.onkeypress = (e) => { if(e.key === 'Enter') askAI(); };
    </script>
    <?php
});