import '../common.scss';
import './style.scss';

const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks
const { getCurrentPostId } = wp.data.select( 'core/editor' );
const Dashicon = wp.components.Dashicon;

const icon = <div className="swp-block-icon" style={ {color: '#429cd6'} }><Dashicon icon="twitter"/></div>

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
registerBlockType( 'social-warfare/click-to-tweet', {
	title: __( 'Click To Tweet' ), // Block title.
	icon: icon,
	category: 'social-warfare', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		// Has a limit of 3 keywords.
		__( 'twitter' ),
		__( 'quote' ),
		__( 'share' )
	],
	attributes: {
	   hasFocus: { type: 'boolean', defualt: false },
	   tweetText: { type: 'string', default: "" },					//* The text to display in the popup dialogue.
	   displayText: { type: 'string', default: "" },				//* The text to display in the post content CTT.
	   overLimit: { type: 'boolean', default: false },				//* If they are over the tweet limit.
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
		 console.log('editing ctt')
		 window.onetwothree=123;
		 const { tweetText, displayText, theme } = props.attributes;
		const styles = ['Default', 'Send Her My Love', 'Roll With The Changes', 'Free Bird', 'Don\'t Stop Believin\'', 'Thunderstruck', 'Livin\' On A Prayer'];
		const characterLimit = 280;
		const color = props.attributes.overLimit ? "rgb(211, 66, 80)" : "";
		const className = props.attributes.overLimit ? "over-limit" : "";

		/**
		 * Local method delcarations.
		 */
		const updateTweetText = ( event ) => {
			  const tweetText = event.target.value;

			if ( !tweetText || !tweetText.length ) {
				return props.setAttributes( { tweetText: '', overLimit: false } );
			}

			const overLimit = tweetText.length > characterLimit;

			 props.setAttributes( { overLimit, tweetText } );
		  }

		const updateDisplayText = ( event ) => {
			 const displayText = event.target.value;

			props.setAttributes( { displayText } );
		 }

		const updateTheme = ( event ) => {
			const index = event.target.value;

			if ( parseInt(index) == 0 ) {
				props.setAttributes( {theme: ''} );
			} else {
				props.setAttributes( {theme: index} );
			}
		}

		const toggleFocus = ( event ) => {
			props.setAttributes( {hasFocus: !props.attributes.hasFocus} );
		}

		//* Inactive state
		if ( !props.attributes.hasFocus ) {
			/**
			 * If no displayText is provided, fallback to use the tweetText.
			 * Else display a "no text provided" messaged.
			 */
			const text = props.attributes.displayText
						 ? props.attributes.displayText
						 : props.attributes.tweetText
							 ? props.attributes.tweetText
							 : "No Click To Tweet text is provided.";

			return (
				<div className='social-warfare-admin-block'>
					<div className={ `${props.className} click-to-tweet-block-wrap swp-inactive-block` }>
						<div className="head" onClick={toggleFocus}>
							{icon}
							<div className="swp-preview">{text}</div>
							<Dashicon className="swp-dashicon" icon="arrow-down" />
						</div>
					 </div>
				</div>
			)
		}

		//* Active state
		 return (
			<div className='social-warfare-admin-block'>
				 <div className={ `${props.className} click-to-tweet-block-wrap swp-active-block` }>
					<div className="head" onClick={toggleFocus}>
						<div>
							{icon}
							<p className="swp-block-title">Click to Tweet</p>
						</div>
						<Dashicon className="swp-dashicon" icon="arrow-up" />
					</div>

					<p>Inserts a <pre style={ {display: 'inline'} }>[click_to_tweet]</pre> shortcode. <a href="https://warfareplugins.com/support/click-to-tweet/">Learn more</a></p>

					<p>Type your tweet as you want it to display <b><em>on Twitter</em></b>:</p>

					<div style={ {width: "100%"} }>
						<p className={`block-characters-remaining ${className}`} style={ {marginTop: -33}}>
							{characterLimit - tweetText.length}
						</p>
						 <textarea name="tweetText"
								   placeholder="Type your tweet. . . "
								   onChange={updateTweetText}
								   value={tweetText}
						  />
					 </div>

					<p>Type your quote as you want it to display <b><em>on the page</em></b>:</p>

					  <textarea name="displayText"
							   placeholder="Type your quote. . . "
							   onChange={updateDisplayText}
							   value={displayText}
					  />

					 <p>Which theme would you like to use for this CTT?</p>

					 <select name="theme"
							 value={theme}
							 onChange={updateTheme}
					 >
					   {
						 styles.map( ( theme, index ) => <option value={index}>{theme}</option> )
					   }
					 </select>
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
		console.log('saving ctt')
		let { tweetText, displayText } = props.attributes;
		if ( !displayText ) {
			displayText = tweetText;
		}

		const theme = props.attributes.theme ? `style${props.attributes.theme}` : '';

		if (!tweetText) return;

		return (
			<div className='social-warfare-admin-block'>
				[click_to_tweet tweet="{tweetText}" quote="{displayText}" theme="{theme}"]
			</div>
		);
	},
} );
