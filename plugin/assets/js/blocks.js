( function ( blocks, components, compose, data, editPost, element, i18n, plugins ) {
	const el = element.createElement;
	const __ = i18n.__;
	const useEffect = element.useEffect;
	const useState = element.useState;
	const useSelect = data.useSelect;
	const PluginDocumentSettingPanel = editPost && editPost.PluginDocumentSettingPanel;
	const TextControl = components.TextControl;
	const TextareaControl = components.TextareaControl;
	const Button = components.Button;
	const Spinner = components.Spinner;
	const Notice = components.Notice;
	const PanelBody = components.PanelBody;
	const registerPlugin = plugins.registerPlugin;

	function cloneArray( items ) {
		return Array.isArray( items ) ? items.slice() : [];
	}

	function coverPreviewUrl( postId ) {
		const base = window.nervCoreEditor && window.nervCoreEditor.coverRestBase;
		if ( ! base || ! postId ) {
			return '';
		}
		return base + postId;
	}

	function coverGenerateUrl( postId ) {
		const base = window.nervCoreEditor && window.nervCoreEditor.coverGenerateBase;
		if ( ! base || ! postId ) {
			return '';
		}
		return base + postId;
	}

	function coverRestoreUrl( postId ) {
		const base = window.nervCoreEditor && window.nervCoreEditor.coverRestoreBase;
		if ( ! base || ! postId ) {
			return '';
		}
		return base + postId;
	}

	function keyPointsGenerateUrl( postId ) {
		const base = window.nervCoreEditor && window.nervCoreEditor.keyPointsGenerateBase;
		if ( ! base || ! postId ) {
			return '';
		}
		return base + postId;
	}

	function geoScoreUrl( postId ) {
		const base = window.nervCoreEditor && window.nervCoreEditor.geoScoreBase;
		if ( ! base || ! postId ) {
			return '';
		}
		return base + postId;
	}

	function geoWeightsUrl() {
		return window.nervCoreEditor && window.nervCoreEditor.geoWeightsPath ? window.nervCoreEditor.geoWeightsPath : '';
	}

	function restFetch( url, options ) {
		return window
			.fetch(
				url,
				Object.assign(
					{
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': window.nervCoreEditor ? window.nervCoreEditor.nonce : '',
						},
					},
					options || {}
				)
			)
			.then( function ( response ) {
				return response.json().then( function ( data ) {
					if ( ! response.ok ) {
						throw data;
					}
					return data;
				} );
			} );
	}

	function coverRatioFrame( label, ratio, src ) {
		return el(
			'div',
			{ className: 'nerv-cover-preview-frame nerv-cover-preview-frame--' + ratio },
			el( 'div', { className: 'nerv-cover-preview-frame__label' }, label ),
			src
				? el( 'img', { src: src, alt: label } )
				: el( 'div', { className: 'nerv-cover-preview-frame__empty' }, __( 'No cover URL available.', 'nerv-core' ) )
		);
	}

	function NervCoverPanel() {
		const postState = useSelect( function ( select ) {
			const editor = select( 'core/editor' );
			const core = select( 'core' );
			const featuredMedia = editor.getEditedPostAttribute( 'featured_media' );
			return {
				postId: editor.getCurrentPostId(),
				featuredMedia: featuredMedia,
				featuredMediaObject: featuredMedia ? core.getMedia( featuredMedia ) : null,
			};
		}, [] );
		const postId = postState.postId;
		const featuredMedia = postState.featuredMedia;
		const featuredMediaObject = postState.featuredMediaObject;
		const [ preview, setPreview ] = useState( null );
		const [ loading, setLoading ] = useState( false );
		const [ generating, setGenerating ] = useState( false );
		const [ restoringIndex, setRestoringIndex ] = useState( null );
		const [ error, setError ] = useState( '' );
		const [ resultMessage, setResultMessage ] = useState( '' );

		useEffect(
			function () {
				const url = coverPreviewUrl( postId );
				if ( ! url ) {
					return;
				}

				let alive = true;
				setLoading( true );
				setError( '' );
				window
					.fetch( url, {
						credentials: 'same-origin',
						headers: {
							'X-WP-Nonce': window.nervCoreEditor ? window.nervCoreEditor.nonce : '',
						},
					} )
					.then( function ( response ) {
						if ( ! response.ok ) {
							throw new Error( response.statusText || 'REST error' );
						}
						return response.json();
					} )
					.then( function ( data ) {
						if ( alive ) {
							setPreview( data );
						}
					} )
					.catch( function () {
						if ( alive ) {
							setError( __( 'Cover preview could not be loaded.', 'nerv-core' ) );
						}
					} )
					.finally( function () {
						if ( alive ) {
							setLoading( false );
						}
					} );

				return function () {
					alive = false;
				};
			},
			[ postId, featuredMedia ]
		);

		const status = preview && preview.status ? preview.status : {};
		const editedUploadUrl =
			featuredMediaObject &&
			( featuredMediaObject.media_details &&
			featuredMediaObject.media_details.sizes &&
			featuredMediaObject.media_details.sizes.full
				? featuredMediaObject.media_details.sizes.full.source_url
				: featuredMediaObject.source_url );
		const ratio5x2 = editedUploadUrl || ( preview ? preview.ratio5x2 : '' );
		const ratio2x1 = editedUploadUrl || ( preview ? preview.ratio2x1 : '' );
		const sourceLabel = editedUploadUrl ? 'UPLOAD' : preview && preview.sourceLabel ? preview.sourceLabel : 'SVG';
		const history = preview && Array.isArray( preview.history ) ? preview.history : [];
		const generateDisabled = generating || ( ! status.ready && ! status.dryRun );
		const busy = generating || restoringIndex !== null;

		function generateCover() {
			const url = coverGenerateUrl( postId );
			if ( ! url ) {
				return;
			}

			setGenerating( true );
			setError( '' );
			setResultMessage( '' );
			window
				.fetch( url, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'X-WP-Nonce': window.nervCoreEditor ? window.nervCoreEditor.nonce : '',
					},
				} )
				.then( function ( response ) {
					return response.json().then( function ( data ) {
						if ( ! response.ok ) {
							throw data;
						}
						return data;
					} );
				} )
				.then( function ( data ) {
					if ( data.preview ) {
						setPreview( data.preview );
					}
					if ( data.result && data.result.message ) {
						setResultMessage( data.result.message );
					}
				} )
				.catch( function ( data ) {
					setError( data && data.result && data.result.message ? data.result.message : __( 'Cover generation failed.', 'nerv-core' ) );
				} )
				.finally( function () {
					setGenerating( false );
				} );
		}

		function restoreCover( index ) {
			const url = coverRestoreUrl( postId );
			if ( ! url ) {
				return;
			}

			setRestoringIndex( index );
			setError( '' );
			setResultMessage( '' );
			window
				.fetch( url, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': window.nervCoreEditor ? window.nervCoreEditor.nonce : '',
					},
					body: JSON.stringify( { index: index } ),
				} )
				.then( function ( response ) {
					return response.json().then( function ( data ) {
						if ( ! response.ok ) {
							throw data;
						}
						return data;
					} );
				} )
				.then( function ( data ) {
					if ( data.preview ) {
						setPreview( data.preview );
					}
					if ( data.result && data.result.message ) {
						setResultMessage( data.result.message );
					}
				} )
				.catch( function ( data ) {
					setError( data && data.result && data.result.message ? data.result.message : __( 'Cover restore failed.', 'nerv-core' ) );
				} )
				.finally( function () {
					setRestoringIndex( null );
				} );
		}

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'nerv-cover',
				title: __( 'NERV COVER', 'nerv-core' ),
				className: 'nerv-cover-panel',
			},
			loading ? el( Spinner, null ) : null,
			error
				? el( Notice, { status: 'warning', isDismissible: false }, error )
				: null,
			resultMessage
				? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setResultMessage( '' ); } }, resultMessage )
				: null,
			preview
				? el(
						'div',
						{ className: 'nerv-cover-preview' },
						el(
							'div',
							{ className: 'nerv-cover-preview__status' },
							el( 'span', null, __( 'SOURCE', 'nerv-core' ) ),
							el( 'strong', null, sourceLabel )
						),
						coverRatioFrame( '5:2 / 1500x600', '5x2', ratio5x2 ),
						coverRatioFrame( '2:1 / 1200x600', '2x1', ratio2x1 ),
						el(
							'p',
							{ className: 'nerv-cover-preview__note' },
							status.message || __( 'Upload covers and SVG fallback are available.', 'nerv-core' )
						),
						preview.prompt
							? el(
									'details',
									{ className: 'nerv-cover-preview__prompt' },
									el( 'summary', null, __( 'Prompt preview', 'nerv-core' ) ),
									el( 'p', null, preview.prompt )
							  )
							: null,
						el(
							Button,
							{
								variant: 'secondary',
								disabled: busy || generateDisabled,
								isBusy: generating,
								onClick: generateCover,
							},
							generating
								? __( 'Generating...', 'nerv-core' )
								: generateDisabled
								? __( 'AI not configured', 'nerv-core' )
								: __( 'Generate cover', 'nerv-core' )
						),
						history.length
							? el(
									'div',
									{ className: 'nerv-cover-history' },
									el( 'strong', null, __( 'History', 'nerv-core' ) ),
									el(
										'ul',
										null,
										history.slice( 0, 4 ).map( function ( item, index ) {
											const canRestore = !! ( item && ( item.attachment_id || item.url ) );
											return el(
												'li',
												{ key: index },
												el(
													'div',
													{ className: 'nerv-cover-history__meta' },
													el( 'span', null, ( item.status || '' ).toUpperCase() ),
													el( 'small', null, item.time || '' )
												),
												el( 'p', null, item.message || '' ),
												canRestore
													? el(
															'div',
															{ className: 'nerv-cover-history__actions' },
															el(
																Button,
																{
																	variant: 'tertiary',
																	isSmall: true,
																	disabled: busy,
																	isBusy: restoringIndex === index,
																	onClick: function () {
																		restoreCover( index );
																	},
																},
																restoringIndex === index ? __( 'Using...', 'nerv-core' ) : __( 'Use', 'nerv-core' )
															)
													  )
													: null
											);
										} )
									)
							  )
							: null
				  )
				: null
		);
	}

	function geoScoreClass( grade ) {
		return 'nerv-geo-score-card nerv-geo-score-card--' + String( grade || 'red' ).toLowerCase();
	}

	function NervGeoScorePanel() {
		const postState = useSelect( function ( select ) {
			const editor = select( 'core/editor' );
			return {
				postId: editor.getCurrentPostId(),
				content: editor.getEditedPostContent(),
				meta: editor.getEditedPostAttribute( 'meta' ) || {},
				featuredMedia: editor.getEditedPostAttribute( 'featured_media' ) || 0,
			};
		}, [] );
		const [ result, setResult ] = useState( null );
		const [ weights, setWeights ] = useState( null );
		const [ defaults, setDefaults ] = useState( null );
		const [ loading, setLoading ] = useState( false );
		const [ saving, setSaving ] = useState( false );
		const [ error, setError ] = useState( '' );
		const [ notice, setNotice ] = useState( '' );
		const postId = postState.postId;
		const subtitle = postState.meta && postState.meta._nerv_subtitle ? postState.meta._nerv_subtitle : '';

		useEffect(
			function () {
				const url = geoWeightsUrl();
				if ( ! url ) {
					return;
				}

				let alive = true;
				restFetch( url, { method: 'GET' } )
					.then( function ( data ) {
						if ( ! alive ) {
							return;
						}
						setWeights( data.weights || {} );
						setDefaults( data.defaults || data.weights || {} );
					} )
					.catch( function () {
						if ( alive ) {
							setError( __( 'GEO weights could not be loaded.', 'nerv-core' ) );
						}
					} );

				return function () {
					alive = false;
				};
			},
			[]
		);

		useEffect(
			function () {
				const url = geoScoreUrl( postId );
				if ( ! url || ! weights ) {
					return;
				}

				let alive = true;
				setLoading( true );
				setError( '' );
				const timer = window.setTimeout( function () {
					restFetch( url, {
						method: 'POST',
						body: JSON.stringify( {
							content: postState.content || '',
							meta: postState.meta || {},
							subtitle: subtitle,
							featuredMedia: postState.featuredMedia || 0,
							weights: weights,
						} ),
					} )
						.then( function ( data ) {
							if ( alive ) {
								setResult( data );
							}
						} )
						.catch( function ( data ) {
							if ( alive ) {
								setError( data && data.message ? data.message : __( 'GEO score could not be refreshed.', 'nerv-core' ) );
							}
						} )
						.finally( function () {
							if ( alive ) {
								setLoading( false );
							}
						} );
				}, 350 );

				return function () {
					alive = false;
					window.clearTimeout( timer );
				};
			},
			[ postId, postState.content, subtitle, postState.featuredMedia, JSON.stringify( weights || {} ) ]
		);

		function updateWeight( key, value ) {
			const number = Math.max( 0, Math.min( 30, parseInt( value || '0', 10 ) || 0 ) );
			setWeights( Object.assign( {}, weights || {}, { [ key ]: number } ) );
			setNotice( '' );
		}

		function saveWeights() {
			const url = geoWeightsUrl();
			if ( ! url || ! weights ) {
				return;
			}

			setSaving( true );
			setError( '' );
			setNotice( '' );
			restFetch( url, {
				method: 'POST',
				body: JSON.stringify( { weights: weights } ),
			} )
				.then( function ( data ) {
					setWeights( data.weights || weights );
					setDefaults( data.defaults || defaults );
					setNotice( data.message || __( 'GEO score weights saved.', 'nerv-core' ) );
				} )
				.catch( function ( data ) {
					setError( data && data.message ? data.message : __( 'GEO score weights could not be saved.', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		function restoreDefaults() {
			if ( defaults ) {
				setWeights( Object.assign( {}, defaults ) );
				setNotice( __( 'Default weights restored in this preview. Save to apply globally.', 'nerv-core' ) );
			}
		}

		function insertBlock( blockName, attributes, innerBlocks ) {
			if ( ! window.wp || ! window.wp.blocks || ! window.wp.data ) {
				return false;
			}
			const block = window.wp.blocks.createBlock( blockName, attributes || {}, innerBlocks || [] );
			window.wp.data.dispatch( 'core/block-editor' ).insertBlocks( block );
			return true;
		}

		function repairGeoCheck( check ) {
			const key = check && check.key ? check.key : '';
			setError( '' );
			setNotice( '' );

			if ( 'subtitle' === key ) {
				const input = document.querySelector( '#nerv-core-subtitle' );
				if ( input && input.focus ) {
					input.focus();
					setNotice( __( 'Subtitle field focused. Add a compact alternative headline.', 'nerv-core' ) );
				} else {
					setNotice( __( 'Open the NERV Entry Details panel and fill the subtitle field.', 'nerv-core' ) );
				}
				return;
			}

			if ( 'key_points' === key ) {
				if ( insertBlock( 'nerv-core/key-points', { points: [ __( 'Add the primary answer in one sentence.', 'nerv-core' ), __( 'Explain the reader outcome or operating context.', 'nerv-core' ), __( 'Name the next useful resource or action.', 'nerv-core' ) ] } ) ) {
					setNotice( __( 'KEY POINTS block inserted. Edit the three draft points.', 'nerv-core' ) );
				}
				return;
			}

			if ( 'h2' === key ) {
				if ( insertBlock( 'core/heading', { level: 2, content: __( 'Operational Context', 'nerv-core' ) } ) ) {
					setNotice( __( 'H2 heading inserted.', 'nerv-core' ) );
				}
				return;
			}

			if ( 'definition' === key ) {
				if ( insertBlock( 'core/paragraph', { content: __( 'This article defines the core operating context, key terms, and recommended next actions for readers and AI systems.', 'nerv-core' ) } ) ) {
					setNotice( __( 'Definition paragraph inserted. Move it near the opening if needed.', 'nerv-core' ) );
				}
				return;
			}

			if ( 'faq' === key ) {
				if ( insertBlock( 'nerv-core/faq', { items: [ { question: __( 'What should readers understand first?', 'nerv-core' ), answer: __( 'Readers should understand the direct answer, context, and next action without scanning the whole page.', 'nerv-core' ) } ] } ) ) {
					setNotice( __( 'FAQ block inserted with a starter question.', 'nerv-core' ) );
				}
				return;
			}

			if ( 'word_count' === key ) {
				if ( insertBlock( 'core/paragraph', { content: __( 'Add a supporting paragraph with concrete context, examples, constraints, and next steps so the article can stand alone for human readers and AI crawlers.', 'nerv-core' ) } ) ) {
					setNotice( __( 'Expansion paragraph inserted.', 'nerv-core' ) );
				}
				return;
			}

			if ( 'internal_links' === key ) {
				if ( insertBlock( 'core/paragraph', { content: __( 'Related resources: add two internal links to relevant posts, projects, or policy pages.', 'nerv-core' ) } ) ) {
					setNotice( __( 'Internal-link prompt inserted. Replace the text with real internal links.', 'nerv-core' ) );
				}
				return;
			}

			if ( 'cover' === key ) {
				if ( window.wp && window.wp.data ) {
					window.wp.data.dispatch( 'core/edit-post' ).openGeneralSidebar( 'edit-post/document' );
				}
				setNotice( __( 'Open the Featured image control and set or generate a cover.', 'nerv-core' ) );
				return;
			}

			if ( 'freshness' === key ) {
				setNotice( __( 'Update the article content and save to refresh the modified date.', 'nerv-core' ) );
				return;
			}

			setNotice( check && check.suggestion ? check.suggestion : __( 'Follow the GEO suggestion and refresh the score.', 'nerv-core' ) );
		}

		const checks = result && Array.isArray( result.checks ) ? result.checks : [];
		const failed = checks.filter( function ( check ) {
			return ! check.passed;
		} );

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'nerv-geo-score',
				title: __( 'NERV GEO SCORE', 'nerv-core' ),
				className: 'nerv-geo-score-panel',
			},
			loading ? el( Spinner, null ) : null,
			error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
			notice
				? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice )
				: null,
			result
				? el(
						'div',
						{ className: 'nerv-geo-score-preview' },
						el(
							'div',
							{ className: geoScoreClass( result.grade ) },
							el( 'span', null, __( 'GEO SCORE', 'nerv-core' ) ),
							el( 'strong', null, String( result.score || 0 ) ),
							el( 'em', null, '/100 ' + ( result.grade || 'RED' ) )
						),
						el(
							'div',
							{ className: 'nerv-geo-score-metrics' },
							el( 'span', null, __( 'Words', 'nerv-core' ) + ': ' + String( result.word_count || 0 ) ),
							el( 'span', null, __( 'Internal links', 'nerv-core' ) + ': ' + String( result.internal_links || 0 ) ),
							el( 'span', null, __( 'Updated', 'nerv-core' ) + ': ' + String( result.fresh_days || 0 ) + 'd' )
						),
						el(
							'ul',
							{ className: 'nerv-geo-score-checks' },
							checks.map( function ( check ) {
								return el(
									'li',
									{
										key: check.key,
										className: check.passed ? 'is-pass' : 'is-fail',
									},
									el(
										'div',
										null,
										el( 'strong', null, ( check.passed ? 'PASS ' : 'FIX ' ) + check.label ),
										el( 'span', null, '+' + String( check.points || 0 ) )
									),
									check.passed ? null : el( 'p', null, check.suggestion || '' ),
									check.passed
										? null
										: el(
												Button,
												{
													variant: 'secondary',
													isSmall: true,
													onClick: function () {
														repairGeoCheck( check );
													},
												},
												__( 'Repair', 'nerv-core' )
										  )
								);
							} )
						),
						failed.length
							? el(
									'div',
									{ className: 'nerv-geo-score-next' },
									el( 'strong', null, __( 'Next repair', 'nerv-core' ) ),
									el( 'p', null, failed[ 0 ].suggestion || '' ),
									el(
										Button,
										{
											variant: 'primary',
											isSmall: true,
											onClick: function () {
												repairGeoCheck( failed[ 0 ] );
											},
										},
										__( 'Repair next item', 'nerv-core' )
									)
							  )
							: el(
									'div',
									{ className: 'nerv-geo-score-next is-clear' },
									el( 'strong', null, __( 'Launch-ready', 'nerv-core' ) ),
									el( 'p', null, __( 'All active GEO checks are passing for this draft.', 'nerv-core' ) )
							  )
				  )
				: null,
			weights
				? el(
						PanelBody,
						{ title: __( 'Scoring weights', 'nerv-core' ), initialOpen: false },
						el(
							'div',
							{ className: 'nerv-geo-score-weights' },
							Object.keys( weights ).map( function ( key ) {
								const label = checks.find( function ( check ) {
									return check.key === key;
								} );
								return el( TextControl, {
									key: key,
									type: 'number',
									min: 0,
									max: 30,
									label: label ? label.label : key,
									value: String( weights[ key ] ),
									__next40pxDefaultSize: true,
									onChange: function ( value ) {
										updateWeight( key, value );
									},
								} );
							} )
						),
						el(
							'div',
							{ className: 'nerv-geo-score-actions' },
							el(
								Button,
								{
									variant: 'secondary',
									isSmall: true,
									disabled: saving,
									onClick: restoreDefaults,
								},
								__( 'Restore defaults', 'nerv-core' )
							),
							el(
								Button,
								{
									variant: 'primary',
									isSmall: true,
									isBusy: saving,
									disabled: saving,
									onClick: saveWeights,
								},
								saving ? __( 'Saving...', 'nerv-core' ) : __( 'Save weights', 'nerv-core' )
							)
						)
				  )
				: null
		);
	}

	blocks.registerBlockType( 'nerv-core/key-points', {
		title: __( 'NERV Key Points', 'nerv-core' ),
		icon: 'list-view',
		category: 'widgets',
		description: __( 'A NERV-styled summary panel for GEO-ready article takeaways.', 'nerv-core' ),
		attributes: {
			title: {
				type: 'string',
				default: __( 'KEY POINTS / 要点提取', 'nerv-core' ),
			},
			points: {
				type: 'array',
				default: [
					__( 'State the main answer in one clear sentence.', 'nerv-core' ),
					__( 'Keep each point short enough for AI summaries.', 'nerv-core' ),
					__( 'Link the article to a concrete operating context.', 'nerv-core' ),
				],
			},
		},
		edit: function ( props ) {
			const attrs = props.attributes;
			const points = cloneArray( attrs.points );
			const postState = useSelect( function ( select ) {
				const editor = select( 'core/editor' );
				return {
					postId: editor.getCurrentPostId(),
					title: editor.getEditedPostAttribute( 'title' ) || '',
					content: editor.getEditedPostContent(),
					meta: editor.getEditedPostAttribute( 'meta' ) || {},
				};
			}, [] );
			const [ generatingPoints, setGeneratingPoints ] = useState( false );
			const [ generationError, setGenerationError ] = useState( '' );
			const [ generationMessage, setGenerationMessage ] = useState( '' );
			const setPoint = function ( index, value ) {
				const next = cloneArray( points );
				next[ index ] = value;
				props.setAttributes( { points: next } );
			};
			const removePoint = function ( index ) {
				const next = cloneArray( points );
				next.splice( index, 1 );
				props.setAttributes( { points: next } );
			};
			const generatePoints = function () {
				const url = keyPointsGenerateUrl( postState.postId );
				if ( ! url ) {
					return;
				}

				setGeneratingPoints( true );
				setGenerationError( '' );
				setGenerationMessage( '' );
				restFetch( url, {
					method: 'POST',
					body: JSON.stringify( {
						title: postState.title,
						content: postState.content || '',
						meta: postState.meta || {},
						subtitle: postState.meta && postState.meta._nerv_subtitle ? postState.meta._nerv_subtitle : '',
					} ),
				} )
					.then( function ( data ) {
						if ( Array.isArray( data.points ) && data.points.length ) {
							props.setAttributes( { points: data.points.slice( 0, 5 ) } );
						}
						setGenerationMessage( data.message || __( 'KEY POINTS generated.', 'nerv-core' ) );
					} )
					.catch( function ( data ) {
						setGenerationError( data && data.message ? data.message : __( 'KEY POINTS generation failed.', 'nerv-core' ) );
					} )
					.finally( function () {
						setGeneratingPoints( false );
					} );
			};

			return el(
				'div',
				{ className: 'nerv-geo-block nerv-key-points' },
				generationError
					? el( Notice, { status: 'warning', isDismissible: false }, generationError )
					: null,
				generationMessage
					? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setGenerationMessage( '' ); } }, generationMessage )
					: null,
				el(
					'div',
					{ className: 'nerv-geo-block__heading' },
					el( 'span', null, 'GEO' ),
					el( TextControl, {
						label: __( 'Panel title', 'nerv-core' ),
						value: attrs.title,
						__next40pxDefaultSize: true,
						onChange: function ( value ) {
							props.setAttributes( { title: value } );
						},
					} )
				),
				el(
					'ol',
					null,
					points.map( function ( point, index ) {
						return el(
							'li',
							{ key: index },
							el( TextControl, {
								label: __( 'Point', 'nerv-core' ) + ' ' + ( index + 1 ),
								value: point,
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									setPoint( index, value );
								},
							} ),
							el(
								Button,
								{
									isSmall: true,
									isDestructive: true,
									onClick: function () {
										removePoint( index );
									},
								},
								__( 'Remove', 'nerv-core' )
							)
						);
					} )
				),
				el(
					Button,
					{
						variant: 'secondary',
						onClick: function () {
							props.setAttributes( { points: points.concat( [ '' ] ) } );
						},
					},
					__( 'Add point', 'nerv-core' )
				),
				el(
					'div',
					{ className: 'nerv-key-points-actions' },
					el(
						Button,
						{
							variant: 'primary',
							isBusy: generatingPoints,
							disabled: generatingPoints || ! postState.postId,
							onClick: generatePoints,
						},
						generatingPoints ? __( 'Generating...', 'nerv-core' ) : __( 'Generate KEY POINTS', 'nerv-core' )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	blocks.registerBlockType( 'nerv-core/faq', {
		title: __( 'NERV FAQ', 'nerv-core' ),
		icon: 'editor-help',
		category: 'widgets',
		description: __( 'Question and answer block that emits FAQPage JSON-LD.', 'nerv-core' ),
		attributes: {
			title: {
				type: 'string',
				default: __( 'FAQ / よくある質問', 'nerv-core' ),
			},
			items: {
				type: 'array',
				default: [
					{
						question: __( 'What should readers remember first?', 'nerv-core' ),
						answer: __( 'Give a direct answer that can stand alone in search and AI summaries.', 'nerv-core' ),
					},
				],
			},
		},
		edit: function ( props ) {
			const attrs = props.attributes;
			const items = cloneArray( attrs.items );
			const setItem = function ( index, key, value ) {
				const next = cloneArray( items );
				next[ index ] = Object.assign( {}, next[ index ], { [ key ]: value } );
				props.setAttributes( { items: next } );
			};
			const removeItem = function ( index ) {
				const next = cloneArray( items );
				next.splice( index, 1 );
				props.setAttributes( { items: next } );
			};

			return el(
				'div',
				{ className: 'nerv-geo-block nerv-faq' },
				el(
					PanelBody,
					{ title: __( 'FAQ block', 'nerv-core' ), initialOpen: true },
					el( TextControl, {
						label: __( 'Panel title', 'nerv-core' ),
						value: attrs.title,
						__next40pxDefaultSize: true,
						onChange: function ( value ) {
							props.setAttributes( { title: value } );
						},
					} ),
					items.map( function ( item, index ) {
						return el(
							'div',
							{ key: index, className: 'nerv-faq-editor-item' },
							el( TextControl, {
								label: __( 'Question', 'nerv-core' ) + ' ' + ( index + 1 ),
								value: item.question || '',
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									setItem( index, 'question', value );
								},
							} ),
							el( TextareaControl, {
								label: __( 'Answer', 'nerv-core' ) + ' ' + ( index + 1 ),
								value: item.answer || '',
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									setItem( index, 'answer', value );
								},
							} ),
							el(
								Button,
								{
									isSmall: true,
									isDestructive: true,
									onClick: function () {
										removeItem( index );
									},
								},
								__( 'Remove FAQ', 'nerv-core' )
							)
						);
					} ),
					el(
						Button,
						{
							variant: 'secondary',
							onClick: function () {
								props.setAttributes( {
									items: items.concat( [ { question: '', answer: '' } ] ),
								} );
							},
						},
						__( 'Add FAQ', 'nerv-core' )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	if ( registerPlugin && PluginDocumentSettingPanel ) {
		registerPlugin( 'nerv-core-cover-panel', {
			render: NervCoverPanel,
			icon: 'format-image',
		} );
		registerPlugin( 'nerv-core-geo-score-panel', {
			render: NervGeoScorePanel,
			icon: 'chart-bar',
		} );
	}
} )(
	window.wp.blocks,
	window.wp.components,
	window.wp.compose,
	window.wp.data,
	window.wp.editPost,
	window.wp.element,
	window.wp.i18n,
	window.wp.plugins
);
