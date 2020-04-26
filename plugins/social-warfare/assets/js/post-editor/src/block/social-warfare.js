
import './style.scss';

const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks
const Dashicon = wp.components.Dashicon;
const icon = (<div className="swp-block-icon">
				<svg version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 32 32" enable-background="new 0 0 32 32">
					<path fill="#ee464f" d="M8.6,9.9c0.2-0.8,1.8-4.2,5.5-6.3C8.1,4.5,3.5,9.7,3.5,15.9c0,1.6,0.3,3.2,0.9,4.6c0.2-0.2,0.5-0.3,0.8-0.3
					l4.6-0.9c0.8-0.2,1.1,0.2,0.9,1c-0.5,1.8,0.5,2.9,2.3,2.9c1.8,0,3.6-1.1,3.7-2.1C17.1,17.8,5.5,18.5,8.6,9.9z M27.2,10.4
					c-0.3,0.3-0.6,0.6-1.1,0.7L21.4,12c-0.8,0.2-1.1-0.2-0.9-0.9c0.3-1.5-0.6-2.5-2.4-2.5c-1.5,0-2.7,0.9-2.8,1.7
					c-0.5,2.9,11.4,2.9,8.4,11.5c-0.3,0.8-2.3,4.6-6.8,6.6c6.5-0.4,11.7-5.8,11.7-12.4C28.5,14,28,12.1,27.2,10.4z"/>
				</svg>
			</div>);

/**
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */
registerBlockType( 'social-warfare/social-warfare', {
	title: __( 'Social Warfare' ), // Block title.
	icon: icon,
	category: 'social-warfare', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		// Has a limit of 3 keywords.
		__( 'share' ),
		__( 'button' ),
		__( 'tweet' )
	],
	attributes: {
	   hasFocus: { type: 'boolean', defualt: false },		//* Used only for editor to display either slim or full block.
	   useThisPost: { type: 'string', default: "this" },	//* Option to use share data from this post, or another post.
	   postID: { type: 'number', default: ''},              //* If ${useThisPost} == 'other', the ID of target post to fetch data from.
	   buttons: { type: 'string', default: '' },			//* A csv of valid networks to display in the shortcode.
   },

	/**
	 * The edit function describes the structure of your block in the context of the editor.
	 * This represents what the editor will render when the block is used.
	 *
	 * The "edit" property must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 */
	edit: function( props ) {
		const { useThisPost, buttons, postID } = props.attributes;

		const toggleFocus = ( event ) => {
			props.setAttributes( {hasFocus: !props.attributes.hasFocus} );
		}

		const updateAttributes = ( event ) => {
			props.setAttributes( {[event.target.name]: event.target.value} );
		}

		const updateButtonsList = ( event ) => {
			props.setAttributes( {buttons: event.target.value} );
		}

		const updatePostID = ( event ) => {
			const postID = wp.data.select('core/editor').getCurrentPostId();
			const value = event.target.value;

			if ( value == '' ) {
				props.setAttributes( { postID: "" } )
				return;
			}

			if ( isNaN( parseInt( value ) ) ) {
				return;
			}

			props.setAttributes( { postID: parseInt(value) } )
		}

		//* Inactive state
		if ( !props.attributes.hasFocus ) {
			const buttons = props.attributes.buttons && props.attributes.buttons.length
							? `buttons="${props.attributes.buttons}"` : '';

			const postID = props.attributes.useThisPost == "other"
							? `id="${props.attributes.postID}"` : '';

			return (
				<div className='social-warfare-admin-block'>
					<div className={ `${props.className} social-warfare-block-wrap swp-inactive-block` }>
						<div className="head" onClick={toggleFocus}>
							{icon}
							<div className="swp-preview">[social_warfare {buttons} {postID}]</div>
							<Dashicon className="swp-dashicon" icon="arrow-down" />
						</div>
					</div>
				</div>
			);
		}

		//* Active state
		return (
			<div className='social-warfare-admin-block'>
				<div className={ `${props.className} social-warfare-block-wrap swp-active-block` }>
					<div className="head" onClick={toggleFocus}>
						<div>
							{icon}
							<p className="swp-block-title">Social Warfare Shortcode</p>
						</div>
						<Dashicon className="swp-dashicon" icon="arrow-down" />
					</div>

					<p>Inserts a <pre style={ {display: 'inline'} }>[social_warfare]</pre> shortcode. Leave a field blank to use values based on your global settings. <a href="https://warfareplugins.com/support/using-shortcodes-and-php-snippets/">Learn more</a></p>

					<p>Should the buttons reflect this post, or a different post?</p>

					<select   name='useThisPost'
							  value={props.attributes.useThisPost == "other" ? "other" : "this"}
							  onChange={updateAttributes}
					>
					  <option value="this">This post</option>
					  <option value="other">Another post</option>
					</select>

					{
					  props.attributes.useThisPost == "other" &&
					  <div>
						  <p>Which post should we fetch SW settings and shares from?</p>
						  <input type="text"
								 onChange ={updatePostID}
								 value={props.attributes.postID}
						  />
					  </div>
					}

					<p>Which networks should we display? Leave blank to use your global settings. </p>
					<input value={props.attributes.buttons}
						   type="text"
						   onChange={updateButtonsList}
					/>
				</div>
			</div>
		);
	},

	/**
	 * The save function defines the way in which the different attributes should be combined
	 * into the final markup, which is then serialized by Gutenberg into post_content.
	 *
	 * The "save" property must be specified and must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 */
	save: function( props ) {
		const buttons = props.attributes.buttons && props.attributes.buttons.length
						? `buttons="${props.attributes.buttons}"` : '';

		const postID = props.attributes.useThisPost == "other"
						? `id="${props.attributes.postID}"` : '';

		return (
			<div>
				[social_warfare {buttons} {postID}]
			</div>
		);
	},
} );
