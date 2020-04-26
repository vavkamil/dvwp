(function() {
	tinymce.PluginManager.add( 'swp_shortcode_generator', function( editor, url ) {
		editor.addButton( 'swp_shortcode_generator', {
			title: 'Social Warfare Buttons',
			icon: 'sw sw sw-social-warfare',
			onclick: function() {
				editor.windowManager.open( {
					title: 'Insert Social Warfare Buttons',
					body: [
						{
							type: 'listbox',
							name: 'reflection',
							label: 'Should the buttons reflect this post or another one?',
							values: [
								{ text: 'This Post', value: 'default' },
								{ text: 'A Different Post', value: 'alt' }
							],
							onselect: function( v ) {
								if ( this.value() == 'alt' ) {
									jQuery( '.mce-postid' ).parent().parent().slideDown();
								} else {
									jQuery( '.mce-postid' ).parent().parent().slideUp();
								}
							}
						},
						{
							type: 'textbox',
							multiline: false,
							name: 'postID',
							classes: 'postid',
							label: 'The ID of the post or page to reflect:'
						},
						{
							type: 'textbox',
							multiline: false,
							name: 'buttons',
							classes: 'buttons',
							label: 'Buttons to Include:'
						},
						{
							type: 'label',
							name: 'someHelpText',
							onPostRender: function() {
								this.getEl().innerHTML =
								'<span style="float:right;">Comma separated list of social network (e.g. "Facebook,Twitter,Total"). Leave blank to use site-wide defaults.</span>';
							},
							text: ''
						}
					],
					onPostRender: function() {
						jQuery( '.mce-postid' ).parent().parent().slideUp();
						jQuery( '.mce-title' ).prepend( '<i class="sw sw-social-warfare"></i>' );
					},
					onsubmit: function( e ) {
						// Check if this is supposed to refelct a different post_id
						if ( e.data.reflection == 'alt' && e.data.postID != '' ) {
							var post_information = ' post_id="' + e.data.postID + '"';
						} else {
							var post_information = '';
						}
						// Check if this is a custom set of buttons
						if ( e.data.buttons != '' ) {
							var button_set = ' buttons="' + e.data.buttons + '"';
						} else {
							var button_set = '';
						}
						editor.insertContent( '[social_warfare' + post_information + '' + button_set + ']' );
					}
				});
			}
		});
	});
})();
