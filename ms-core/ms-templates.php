<?php
if ( ! defined( 'MS_H_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

// Initializing variables
$tpl = new music_store_tpleng( MS_FILE_PATH . '/ms-templates/sources/', 'comment' );
wp_enqueue_code_editor( array( 'type' => 'text/html' ) );

?>
<div class="wrap">
	<h1 style="margin-bottom:30px;"><?php esc_html_e( 'Customizing the Products Templates', 'music-store' ); ?></h1>
<?php
if ( isset( $_POST['ms_templates'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ms_templates'] ) ), 'ms_templates_nonce' ) ) {
	$message = '';
	if ( isset( $_POST['ms_default_templates'] ) ) {
		update_option( 'song_single.tpl.html', $tpl->get_template_content( 'song_single.tpl.html', true ) );
		update_option( 'song_multiple.tpl.html', $tpl->get_template_content( 'song_multiple.tpl.html', true ) );
		update_option( 'song.tpl.html', $tpl->get_template_content( 'song.tpl.html', true ) );

		$message = __( 'Default Templates Reloaded', 'music-store' );
	} else {
		$_POST = array_map( 'stripcslashes', $_POST );
		update_option( 'ms_custom_templates_active', ( isset( $_POST['ms_custom_templates_active'] ) ) ? 1 : 0 );
		$allowed_tags['tpl'] = array( 'ifset' => true );
		if ( ! empty( $_POST['song_single_tpl'] ) ) {
			update_option( 'song_single.tpl.html', wp_unslash( $_POST['song_single_tpl'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}
		if ( ! empty( $_POST['song_multiple_tpl'] ) ) {
			update_option( 'song_multiple.tpl.html', wp_unslash( $_POST['song_multiple_tpl'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}
		if ( ! empty( $_POST['song_tpl'] ) ) {
			update_option( 'song.tpl.html', wp_unslash( $_POST['song_tpl'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		$message = esc_html__( 'Templates Updated', 'music-store' );
	}
	?>
	<div class="updated" style="margin:5px 0;"><strong><?php print wp_kses_post( $message ); ?></strong></div>
	<?php
}

?>
	<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
		<?php esc_html_e( 'This section is accessible only by website administrators. Please, be careful when editing the templates. If editing the templates breaks the products or store pages, first try disabling the custom templates or reload the default ones.', 'music-store' ); ?>
	</p>
	<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
	<?php print wp_kses_post(
		__(
			'If have been associated custom fields to the songs, they can be displayed in the pages of products and store. For example, if has been associated to the song the custom field: my_field, it is possible to include a block similar to the following one, as part of the template structure:<br><br>
	&lt;tpl ifset="my_field"&gt;<br>&lt;div&gt;&lt;label&gt;The label text:&lt;/label&gt;{my_field}&lt;/div&gt;<br>&lt;/tpl ifset="my_field"&gt;',
			'music-store'
		)
	); ?>
	</p>
	<div id="cff_templates_help" style="display:none;position:fixed;width:400px;height:400px;right: 40px; top: 40px; z-index: 9999; background-color:white; border:1px solid #DADADA;">
		<div style="text-align:right;padding:5px;"><a href="javascript:jQuery('#cff_templates_help').hide();">X</a></div>
		<div style="height:340px; overflow:auto;padding:10px;">
<!-- Help -->
<p>The templates are basically html tags, and some few <b>&lt;tpl&gt;</b> tag and <b>vars</b>.</p>
<p>The <b>&lt;tpl&gt;</b> tags, similar to the html tags, are composed of an open and close tag, and accept some self explained attributes, they works as conditional tags:</p>
<p>
<pre>
&lt;tpl
ifset=&quot;song.cover&quot;&gt;
&lt;div class=&quot;song-cover single&quot;&gt;
&lt;img src=&quot;{song.cover}&quot;&gt;
&lt;/div&gt;
&lt;/tpl ifset=&quot;song.cover&quot;&gt;
</pre>
</p>
<p>In the previous block of code  <b>&lt;tpl ifset=&quot;song.cover&quot;&gt;&lt;/tpl ifset=&quot;song.cover&quot;&gt;</b> are the open and close  <b>&lt;tpl&gt;</b> tags, and this means:
</p>
<p>Include the tags:</p>
<p>
<pre>
&lt;div class=&quot;song-cover single&quot;&gt;
&lt;img src=&quot;{song.cover}&quot;&gt;
&lt;/div&gt;
</pre>
</p>
<p>in the page, only if song includes a cover image.</p>
<p>The following example, uses the <b>&lt;tpl&gt;</b> tag with the <b>"loop"</b> attribute:</p>
<p>
<pre>
&lt;tpl loop=&quot;artists&quot;&gt;
&lt;li&gt;&lt;span class=&quot;arrow&quot;&gt;&rsaquo;&lt;/span&gt;{artists.data}&lt;/li&gt;
&lt;/tpl loop=&quot;artists&quot;&gt;
</pre>
</p>
<p>Similar to the previous example, the tags <b>&lt;tpl loop=&quot;artists&quot;&gt;&lt;/tpl loop=&quot;artists&quot;&gt;</b> are the open and close &lt;tpl&gt; tags, and this means:</p>
<p>Repeat the tags:</p>
<p>
<pre>
&lt;li&gt;&lt;span class=&quot;arrow&quot;&gt;&rsaquo;&lt;/span&gt;{artists.data}&lt;/li&gt;
</pre>
</p>
<p>for every item in the "artists" array.</p>
<p>The variables, as you have surely sensed, are represented between symbols: <b>"{}"</b>, in the previous examples, to access the URL of the cover image in songs was used the variable: <b>{song.cover}</b>, and for accessing to the artists information: <b>{artists.data}</b></p>
<p>More information, in the documentation page of the plugin: <a href="https://musicstore.dwbooster.com/documentation#products-templates" target="_blank">Click Here</a></p>
<!-- End Help -->
		</div>
	</div>
	<form method="post" action="<?php echo esc_attr( admin_url( 'admin.php?page=music-store-menu-templates' ) ); ?>">
		<div class="postbox">
			<div class="inside">
				<div style="border-bottom:1px solid #DADADA;padding-bottom:20px; margin-bottom:20px;">
					<label>
						<input type="checkbox" name="ms_custom_templates_active" <?php if ( get_option( 'ms_custom_templates_active' ) ) {
							print 'CHECKED';} ?>>
						<?php esc_html_e( 'Using custom templates', 'music-store' ); ?>
					</label>
					<a href="javascript:jQuery('#cff_templates_help').show();" style="float:right;"><?php esc_html_e( 'Help?', 'music-store' ); ?></a>
				</div>

				<h2><?php esc_html_e( 'Song Templates', 'music-store' ); ?></h2>
				<div>
					<p><b><?php esc_html_e( 'Template used on the songs pages', 'music-store' ); ?></b></p>
					<p>
						<textarea name="song_single_tpl" style="width:100%;" rows="20"><?php
							print esc_textarea( $tpl->get_template_content( 'song_single.tpl.html' ) );
						?></textarea>
					</p>
				</div>
				<div>
					<p><b><?php esc_html_e( 'Template used by the songs on the shop pages', 'music-store' ); ?></b></p>
					<p>
						<textarea name="song_tpl" style="width:100%;" rows="20"><?php
							print esc_textarea( $tpl->get_template_content( 'song.tpl.html' ) );
						?></textarea>
					</p>
				</div>
				<div>
					<p><b><?php esc_html_e( 'Template used by the songs on the archive pages (like gender, artist, album pages)', 'music-store' ); ?></b></p>
					<p>
						<textarea name="song_multiple_tpl" style="width:100%;" rows="20"><?php
							print esc_textarea( $tpl->get_template_content( 'song_multiple.tpl.html' ) );
						?></textarea>
					</p>
				</div>
				<input type="submit" value="<?php esc_attr_e( 'Update', 'music-store' ); ?>" class="button-primary" />
				<input type="button" name="ms_reload_template_button" value="<?php esc_attr_e( 'Reload Default Templates', 'music-store' ); ?>" class="button-secondary" style="float:right;" />
			</div>
		</div>
		<?php
		wp_nonce_field( 'ms_templates_nonce', 'ms_templates' );
		?>
	</form>
</div>
<script>
jQuery(document).on('click', '[name="ms_reload_template_button"]', function(){
	if(confirm('<?php print esc_js( __( 'Do you really want to reload the default templates?', 'music-store' ) ); ?>'))
		jQuery(this).closest('form').append('<input type="hidden" name="ms_default_templates" value="1">').submit();
});
(function($){
	$(function(){
		if('codeEditor' in wp) {
			var editorSettings = wp.codeEditor.defaultSettings ? _.clone( wp.codeEditor.defaultSettings ) : {};
			editorSettings.codemirror = _.extend(
				{},
				editorSettings.codemirror,
				{
					indentUnit: 2,
					tabSize: 2,
					autoCloseTags: false
				}
			);
			editorSettings['htmlhint']['spec-char-escape'] = false;
			editorSettings['htmlhint']['alt-require'] = false;
			editorSettings['htmlhint']['tag-pair'] = false;
			wp.codeEditor.initialize( $('[name="song_single_tpl"]'), editorSettings );
			wp.codeEditor.initialize( $('[name="song_multiple_tpl"]'), editorSettings );
			wp.codeEditor.initialize( $('[name="song_tpl"]'), editorSettings );
		}
	});
 })(jQuery);
</script>
