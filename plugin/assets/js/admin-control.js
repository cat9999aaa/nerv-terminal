( function ( apiFetch, components, element, i18n ) {
	const el = element.createElement;
	const __ = i18n.__;
	const useEffect = element.useEffect;
	const useState = element.useState;
	const Button = components.Button;
	const CheckboxControl = components.CheckboxControl;
	const Spinner = components.Spinner;
	const Notice = components.Notice;
	const TextControl = components.TextControl;
	const TextareaControl = components.TextareaControl;

	function statusClass( state ) {
		return 'nerv-control-light nerv-control-light--' + ( state || 'amber' );
	}

	function metricCard( metric, index ) {
		return el(
			'div',
			{ className: 'nerv-control-metric', key: index },
			el( 'span', null, metric.label || '' ),
			el( 'strong', null, metric.value || '0' )
		);
	}

	function healthRow( item, index ) {
		return el(
			'li',
			{ className: 'nerv-control-health__item', key: index },
			el(
				'div',
				{ className: 'nerv-control-health__line' },
				el( 'span', { className: statusClass( item.state ) } ),
				el( 'strong', null, item.label || '' ),
				el( 'em', null, item.value || '' )
			),
			item.detail ? el( 'p', null, item.detail ) : null
		);
	}

	function tabButton( tab, activeTab, onSelect ) {
		return el(
			'button',
			{
				className: 'nerv-control-tab nerv-control-tab--' + tab.status + ( activeTab === tab.id ? ' is-selected' : '' ),
				key: tab.id,
				type: 'button',
				onClick: function () {
					onSelect( tab.id );
				},
			},
			el( 'span', null, tab.label ),
			el( 'em', null, tab.status.toUpperCase() )
		);
	}

	function stepItem( step, index, handlers ) {
		handlers = handlers || {};
		const actionLabel = step.button || ( step.action ? __( '执行步骤', 'nerv-core' ) : __( '打开步骤', 'nerv-core' ) );
		const sectionLinks = window.nervCoreControl && window.nervCoreControl.sectionLinks ? window.nervCoreControl.sectionLinks : [];
		const targetLink = step.tab ? sectionLinks.find( function ( item ) {
			return item.id === step.tab;
		} ) : null;
		return el(
			'li',
			{ className: step.done ? 'is-done' : 'is-open', key: index },
			el( 'span', null, step.done ? '✓' : String( index + 1 ).padStart( 2, '0' ) ),
			el(
				'div',
				null,
				el( 'strong', null, step.label ),
				step.detail ? el( 'small', null, step.detail ) : null,
				el(
					'div',
					{ className: 'nerv-control-step-actions' },
					step.tab && targetLink ? el(
						'a',
						{
							className: 'button button-secondary button-small',
							href: targetLink.url,
						},
						actionLabel
					) : step.tab ? el(
						Button,
						{
							variant: 'secondary',
							size: 'small',
							disabled: !! handlers.running,
							onClick: function () {
								if ( handlers.onSelectTab ) {
									handlers.onSelectTab( step.tab );
								}
							},
						},
						actionLabel
					) : null,
					step.action ? el(
						Button,
						{
							variant: step.done ? 'secondary' : 'primary',
							size: 'small',
							isBusy: handlers.running === step.action,
							disabled: !! handlers.running,
							onClick: function () {
								if ( handlers.onAction ) {
									handlers.onAction( step.action );
								}
							},
						},
						actionLabel
					) : null
				)
			)
		);
	}

	function activityRows( title, rows, renderer ) {
		return el(
			'section',
			{ className: 'nerv-control-panel' },
			el( 'h3', null, title ),
			rows && rows.length
				? el(
						'ul',
						{ className: 'nerv-control-activity' },
						rows.map( renderer )
				  )
				: el( 'p', { className: 'nerv-control-empty' }, __( '暂无记录。', 'nerv-core' ) )
		);
	}

	function aiProviderDefaults( index ) {
		return {
			id: 'provider-' + String( Date.now() ) + '-' + String( index || 0 ),
			name: '供应商 ' + String( ( index || 0 ) + 1 ),
			type: 'openai_compatible',
			baseUrl: '',
			apiKey: '',
			hasApiKey: false,
			enabled: true,
			modelCache: [],
			modelCacheTime: '',
		};
	}

	function cloneAiFeature( source ) {
		source = source || {};
		return {
			provider_id: source.provider_id || source.providerId || 'default',
			model: source.model || '',
			fallback_models: Array.isArray( source.fallback_models ) ? source.fallback_models : ( Array.isArray( source.fallbackModels ) ? source.fallbackModels : [] ),
		};
	}

	function cloneAiServicesForm( source ) {
		source = source || {};
		const providers = Array.isArray( source.providers ) && source.providers.length ? source.providers.map( function ( provider, index ) {
			return Object.assign( aiProviderDefaults( index ), provider || {}, { apiKey: '' } );
		} ) : [ Object.assign( aiProviderDefaults( 0 ), { id: 'default', name: '默认供应商', baseUrl: source.endpoint || '', modelCache: source.modelCache || [], modelCacheTime: source.modelCacheTime || '', hasApiKey: !! source.hasApiKey } ) ];
		return {
			providers: providers,
			textFeature: cloneAiFeature( source.textFeature || { provider_id: providers[0].id, model: source.model || '', fallback_models: source.fallbackModels || [] } ),
			imageFeature: cloneAiFeature( source.imageFeature || { provider_id: providers[0].id, model: source.model || '', fallback_models: source.fallbackModels || [] } ),
			endpoint: source.endpoint || '',
			apiKey: '',
			model: source.model || '',
			fallbackModels: Array.isArray( source.fallbackModels ) ? source.fallbackModels : [],
			modelCache: Array.isArray( source.modelCache ) ? source.modelCache : [],
			modelCacheTime: source.modelCacheTime || '',
			promptTemplate: source.promptTemplate || '',
			autoGenerate: !! source.autoGenerate,
			keyPointsAuto: !! source.keyPointsAuto,
			dryRun: !! source.dryRun,
		};
	}

	function cloneBrandForm( source ) {
		source = source || {};
		return {
			brandTitle: source.brandTitle || '',
			brandSubtitle: source.brandSubtitle || '',
			brandMark: source.brandMark || '',
			clockLabel: source.clockLabel || '',
			clockTimezone: source.clockTimezone || '',
			activeLabel: source.activeLabel || '',
			pwaName: source.pwaName || '',
			pwaShortName: source.pwaShortName || '',
			themeColor: source.themeColor || '#0A0807',
			brandLogo: Object.assign( { id: 0, url: '', title: '' }, source.brandLogo || {} ),
			brandLogoFit: source.brandLogoFit || 'contain',
			brandLogoFocusX: 'number' === typeof source.brandLogoFocusX ? source.brandLogoFocusX : 50,
			brandLogoFocusY: 'number' === typeof source.brandLogoFocusY ? source.brandLogoFocusY : 50,
			pwaIcon: Object.assign( { id: 0, url: '', title: '' }, source.pwaIcon || {} ),
			pwaIconFit: source.pwaIconFit || 'cover',
			pwaIconFocusX: 'number' === typeof source.pwaIconFocusX ? source.pwaIconFocusX : 50,
			pwaIconFocusY: 'number' === typeof source.pwaIconFocusY ? source.pwaIconFocusY : 50,
			pwaIconSmallSize: 'number' === typeof source.pwaIconSmallSize ? source.pwaIconSmallSize : 192,
			pwaIconLargeSize: 'number' === typeof source.pwaIconLargeSize ? source.pwaIconLargeSize : 512,
			pwaIconAppleSize: 'number' === typeof source.pwaIconAppleSize ? source.pwaIconAppleSize : 180,
			fontCssUrl: source.fontCssUrl || '',
			fontBodyFamily: source.fontBodyFamily || '',
			fontHeadingFamily: source.fontHeadingFamily || '',
			fontMonoFamily: source.fontMonoFamily || '',
		};
	}

	function cloneSeoForm( source ) {
		source = source || {};
		return {
			enabled: source.enabled !== false,
			deferToSeoPlugin: source.deferToSeoPlugin !== false,
			siteDescription: source.siteDescription || '',
			defaultOgImage: Object.assign( { id: 0, url: '', title: '' }, source.defaultOgImage || {} ),
			noindexMarkdown: source.noindexMarkdown !== false,
		};
	}

	function clonePanelsForm( source ) {
		source = source || {};
		return {
			panels: ( source.panels || [] ).map( function ( panel, index ) {
				const fields = {};
				( panel.fields || [] ).forEach( function ( field ) {
					fields[ field.key ] = field.value || '';
				} );
				return {
					id: panel.id || '',
					label: panel.label || '',
					column: panel.column || 'center',
					order: 'number' === typeof panel.order ? panel.order : index,
					enabled: !! panel.enabled,
					fields: fields,
					source: panel.source || 'decorative',
					sourceOptions: panel.sourceOptions || [],
					stateOptions: panel.stateOptions || [],
					rowType: panel.rowType || '',
					rows: ( panel.rows || [] ).map( function ( row ) {
						return {
							label: row.label || '',
							value: row.value || '',
							level: 'undefined' === typeof row.level ? 0 : Number( row.level ) || 0,
							state: row.state || 'green',
						};
					} ),
				};
			} ).sort( function ( a, b ) {
				return a.order === b.order ? a.label.localeCompare( b.label ) : a.order - b.order;
			} ),
			customPanels: ( source.customPanels || [] ).map( function ( panel, index ) {
				return {
					id: panel.id || 'custom_' + Date.now().toString( 36 ) + '_' + index,
					label: panel.label || panel.title || '',
					title: panel.title || panel.label || '',
					subtitle: panel.subtitle || '',
					content: panel.content || '',
					contentType: panel.contentType || 'richtext',
					column: panel.column || 'center',
					order: 'number' === typeof panel.order ? panel.order : 10 + index,
					enabled: 'undefined' === typeof panel.enabled ? true : !! panel.enabled,
				};
			} ).sort( function ( a, b ) {
				return a.order === b.order ? a.title.localeCompare( b.title ) : a.order - b.order;
			} ),
		};
	}

	function normalizePanelOrder( panels ) {
		return ( panels || [] ).map( function ( panel, index ) {
			return Object.assign( {}, panel, { order: index } );
		} );
	}

	function normalizeCustomPanelOrder( panels ) {
		return ( panels || [] ).map( function ( panel, index ) {
			return Object.assign( {}, panel, { order: 10 + index } );
		} );
	}

	function reorderPanels( panels, fromIndex, toIndex ) {
		if ( fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= panels.length || toIndex >= panels.length ) {
			return panels || [];
		}

		const nextPanels = panels.slice();
		const dragged = nextPanels.splice( fromIndex, 1 )[ 0 ];
		nextPanels.splice( toIndex, 0, dragged );
		return nextPanels;
	}

	function cloneGeoForm( source ) {
		source = source || {};
		const indexnow = source.indexnow || {};
		const crawler = source.crawler || {};
		const bots = {};
		( crawler.bots || [] ).forEach( function ( bot ) {
			bots[ bot.key ] = !! bot.enabled;
		} );

		return {
			indexnow: {
				enabled: !! indexnow.enabled,
				key: indexnow.key || '',
				endpoint: indexnow.endpoint || '',
				dryRun: !! indexnow.dryRun,
			},
			crawler: {
				enabled: !! crawler.enabled,
				retentionDays: crawler.retentionDays || 30,
				bots: bots,
			},
		};
	}

	function cloneEffectsForm( source ) {
		source = source || {};
		return {
			enabled: source.enabled !== false,
			backgroundGrid: source.backgroundGrid !== false,
			scanlines: source.scanlines !== false,
			panelGlow: source.panelGlow !== false,
			motion: source.motion !== false,
			intensity: source.intensity || 65,
			preset: source.preset || 'balanced',
			presets: source.presets || [],
			desktop: Object.assign( { enabled: true, intensity: source.intensity || 65 }, source.desktop || {} ),
			mobile: Object.assign( { enabled: true, backgroundGrid: false, scanlines: true, panelGlow: false, motion: true, intensity: 35 }, source.mobile || {} ),
		};
	}

	function cloneAppearanceForm( source ) {
		source = source || {};
		return {
			palette: source.palette || 'hazard',
			mode: source.mode || 'void',
			palettes: source.palettes || [],
			modes: source.modes || [],
			previewUrl: source.previewUrl || '',
		};
	}

	function effectsSettingsPayload( form ) {
		return {
			enabled: form.enabled !== false,
			backgroundGrid: !! form.backgroundGrid,
			scanlines: !! form.scanlines,
			panelGlow: !! form.panelGlow,
			motion: !! form.motion,
			intensity: Math.min( 100, Math.max( 0, parseInt( form.intensity, 10 ) || 0 ) ),
			preset: form.preset || 'custom',
			desktop: {
				enabled: ! form.desktop || form.desktop.enabled !== false,
				intensity: Math.min( 100, Math.max( 0, parseInt( form.desktop && form.desktop.intensity, 10 ) || parseInt( form.intensity, 10 ) || 65 ) ),
			},
			mobile: {
				enabled: ! form.mobile || form.mobile.enabled !== false,
				backgroundGrid: !! ( form.mobile && form.mobile.backgroundGrid ),
				scanlines: ! form.mobile || form.mobile.scanlines !== false,
				panelGlow: !! ( form.mobile && form.mobile.panelGlow ),
				motion: ! form.mobile || form.mobile.motion !== false,
				intensity: Math.min( 100, Math.max( 0, parseInt( form.mobile && form.mobile.intensity, 10 ) || 35 ) ),
			},
		};
	}

	function effectsPresetDocument( form ) {
		return {
			schema: 'nerv-terminal-effects-preset/v1',
			exportedAt: new Date().toISOString(),
			settings: effectsSettingsPayload( form ),
		};
	}

	function mergeEffectsPresetDocument( form, text ) {
		const parsed = JSON.parse( text );
		if ( ! parsed || 'nerv-terminal-effects-preset/v1' !== parsed.schema || ! parsed.settings ) {
			throw new Error( 'Invalid effects preset schema.' );
		}
		const payload = effectsSettingsPayload( parsed.settings );
		const knownPresets = ( form.presets || [] ).map( function ( preset ) {
			return preset.value;
		} );
		payload.preset = knownPresets.indexOf( payload.preset ) === -1 ? 'custom' : payload.preset;

		return Object.assign( {}, form, payload, {
			presets: form.presets || [],
		} );
	}

	function cloneArticlesForm( source ) {
		source = source || {};
		return {
			enabled: !! source.enabled,
			title: source.title || '',
			limit: source.limit || 3,
			categoryWeight: source.categoryWeight || 2,
			tagWeight: source.tagWeight || 1,
			recentWeight: source.recentWeight || 1,
			recentDays: source.recentDays || 180,
			cacheHours: source.cacheHours || 12,
			excludedCategories: ( source.excludedCategories || [] ).map( function ( id ) {
				return parseInt( id, 10 );
			} ).filter( function ( id ) {
				return !! id;
			} ),
		};
	}

	function cloneMobileForm( source ) {
		source = source || {};
		return {
			enabled: source.enabled !== false,
			moreEnabled: source.moreEnabled !== false,
			moreSections: Object.assign(
				{ status: true, monitor: true, alert: true, search: true, footer: true },
				source.moreSections || {}
			),
			tabs: ( source.tabs || [] ).map( function ( tab ) {
				return Object.assign( {}, tab );
			} ),
		};
	}

	function cloneSocialForm( source ) {
		source = source || {};
		return {
			enabled: source.enabled !== false,
			openNewTab: source.openNewTab !== false,
			links: ( source.links || [] ).map( function ( link ) {
				return Object.assign( {}, link );
			} ),
		};
	}

	function clonePartnersForm( source ) {
		source = source || {};
		const display = source.display || {};
		const health = source.health || {};

		return {
			display: {
				footerEnabled: !! display.footerEnabled,
				footerLimit: display.footerLimit || 4,
				applicationEnabled: !! display.applicationEnabled,
				applicationEmail: display.applicationEmail || '',
				applicationText: display.applicationText || '',
				llmsInclude: !! display.llmsInclude,
			},
			health: {
				enabled: !! health.enabled,
				timeout: health.timeout || 5,
				slowSeconds: health.slowSeconds || 2.5,
			},
		};
	}

	function cloneToolsForm( source ) {
		source = source || {};
		return {
			markdown: Object.assign( { eligible: 0, cached: 0, dir: '' }, source.markdown || {} ),
			related: Object.assign( { enabled: false }, source.related || {} ),
			partners: Object.assign( { total: 0, online: 0, slow: 0, offline: 0 }, source.partners || {} ),
			build: Object.assign( { available: false, script: '', commands: {}, themeDir: '', pluginDir: '' }, source.build || {} ),
			demo: Object.assign( { available: false, command: '', counts: { projects: 0, posts: 0, partners: 0 }, ready: false, summary: { created: 0, updated: 0, failed: 0 }, steps: [] }, source.demo || {} ),
			preset: Object.assign( { schema: '', optionGroups: [] }, source.preset || {} ),
			themeCheck: Object.assign( { available: false, status: 'pending', summary: { pass: 0, warning: 0, fail: 0 }, checks: [], message: '' }, source.themeCheck || {} ),
			images: Object.assign( { webpEnabled: false, webpQuality: 0, socialDir: '', queue: {}, mediaQueue: {} }, source.images || {} ),
		};
	}

	function AiServicesPanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.aiServices ? data.forms.aiServices : {};
		const [ form, setForm ] = useState( cloneAiServicesForm( formData ) );
		const [ saving, setSaving ] = useState( false );
		const [ fetchingModels, setFetchingModels ] = useState( '' );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );
		const status = formData.status || {};
		const usage = formData.usage || {};
		const usageServices = usage.services || {};
		const coverUsage = usageServices.cover || {};
		const keyPointsUsage = usageServices.key_points || {};

		function setField( key, value ) {
			setForm( Object.assign( {}, form, { [ key ]: value } ) );
		}

		function updateProvider( index, key, value ) {
			const providers = form.providers.slice();
			providers[ index ] = Object.assign( {}, providers[ index ], { [ key ]: value } );
			setField( 'providers', providers );
		}

		function addProvider() {
			setField( 'providers', form.providers.concat( [ aiProviderDefaults( form.providers.length ) ] ) );
		}

		function removeProvider( index ) {
			if ( form.providers.length <= 1 ) {
				return;
			}
			const removed = form.providers[ index ];
			const providers = form.providers.filter( function ( provider, providerIndex ) {
				return providerIndex !== index;
			} );
			const fallbackProvider = providers[0] ? providers[0].id : 'default';
			setForm( Object.assign( {}, form, {
				providers: providers,
				textFeature: Object.assign( {}, form.textFeature, { provider_id: form.textFeature.provider_id === removed.id ? fallbackProvider : form.textFeature.provider_id } ),
				imageFeature: Object.assign( {}, form.imageFeature, { provider_id: form.imageFeature.provider_id === removed.id ? fallbackProvider : form.imageFeature.provider_id } ),
			} ) );
		}

		function updateFeature( featureKey, key, value ) {
			setField( featureKey, Object.assign( {}, form[ featureKey ], { [ key ]: value } ) );
		}

		function featureFallbackText( feature ) {
			return ( feature.fallback_models || [] ).join( '\n' );
		}

		function setFeatureFallbackText( featureKey, value ) {
			updateFeature( featureKey, 'fallback_models', String( value || '' ).split( /[\r\n,]+/ ).map( function ( item ) {
				return item.trim();
			} ).filter( Boolean ) );
		}

		function toggleFallbackModel( featureKey, model ) {
			const feature = form[ featureKey ];
			const current = feature.fallback_models || [];
			const next = current.indexOf( model ) >= 0 ? current.filter( function ( item ) { return item !== model; } ) : current.concat( [ model ] );
			updateFeature( featureKey, 'fallback_models', next );
		}

		function addCacheToFallbacks( featureKey, models ) {
			const feature = form[ featureKey ];
			const next = ( feature.fallback_models || [] ).slice();
			models.forEach( function ( model ) {
				if ( model !== feature.model && next.indexOf( model ) < 0 ) {
					next.push( model );
				}
			} );
			updateFeature( featureKey, 'fallback_models', next );
		}

		function providerOptions() {
			return form.providers.map( function ( provider ) {
				return el( 'option', { value: provider.id, key: provider.id }, provider.name || provider.id );
			} );
		}

		function findProvider( providerId ) {
			return form.providers.find( function ( provider ) { return provider.id === providerId; } ) || form.providers[0] || {};
		}

		function fetchModels( providerIndex ) {
			const provider = form.providers[ providerIndex ];
			if ( ! provider ) {
				return;
			}
			setFetchingModels( provider.id );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.aiModelsPath : '/nerv-core/v1/control-ai-models',
				method: 'POST',
				data: { providerId: provider.id, providers: form.providers, features: { text: form.textFeature, image: form.imageFeature }, apiKey: provider.apiKey },
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( cloneAiServicesForm( response.dashboard.forms.aiServices ) );
					}
					setNotice( response.message || __( '模型列表已获取并缓存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( '模型列表获取失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setFetchingModels( '' );
				} );
		}

		function saveSettings() {
			setSaving( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.aiServicesPath : '/nerv-core/v1/control-ai-services',
				method: 'POST',
				data: form,
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( cloneAiServicesForm( response.dashboard.forms.aiServices ) );
					}
					setNotice( response.message || __( 'AI供应商设置已保存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( 'AI供应商设置保存失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		function featurePanel( title, featureKey, note ) {
			const feature = form[ featureKey ];
			const provider = findProvider( feature.provider_id );
			const cache = provider.modelCache || [];
			const fallbackModels = feature.fallback_models || [];
			return el(
				'div',
				{ className: 'nerv-control-fieldset' },
				el( 'h4', null, title ),
				el( 'p', { className: 'nerv-control-mini' }, note ),
				el( 'label', { className: 'nerv-control-select-field' },
					el( 'span', null, __( '供应商', 'nerv-core' ) ),
					el( 'select', { value: feature.provider_id, onChange: function ( event ) { updateFeature( featureKey, 'provider_id', event.target.value ); } }, providerOptions() )
				),
				cache.length ? el( 'label', { className: 'nerv-control-select-field' },
					el( 'span', null, __( '主模型', 'nerv-core' ) ),
					el( 'select', { value: feature.model, onChange: function ( event ) { updateFeature( featureKey, 'model', event.target.value ); } },
						el( 'option', { value: '' }, __( '选择主模型', 'nerv-core' ) ),
						cache.map( function ( model ) { return el( 'option', { value: model, key: featureKey + '-main-' + model }, model ); } )
					)
				) : el( TextControl, {
					label: __( '主模型', 'nerv-core' ),
					value: feature.model,
					__next40pxDefaultSize: true,
					onChange: function ( value ) { updateFeature( featureKey, 'model', value ); },
				} ),
				cache.length ? el( 'div', { className: 'nerv-control-model-picker' },
					el( 'div', { className: 'nerv-control-model-picker__head' },
						el( 'strong', null, __( '备用模型选择', 'nerv-core' ) ),
						el( Button, { variant: 'secondary', onClick: function () { addCacheToFallbacks( featureKey, cache ); } }, __( '全部加入备用', 'nerv-core' ) )
					),
					el( 'div', { className: 'nerv-control-model-chip-list' }, cache.slice( 0, 80 ).map( function ( model ) {
						const selected = fallbackModels.indexOf( model ) >= 0;
						const isMain = feature.model === model;
						return el( 'button', {
							type: 'button',
							className: 'nerv-control-model-chip' + ( selected ? ' is-selected' : '' ) + ( isMain ? ' is-main' : '' ),
							key: featureKey + '-fallback-' + model,
							disabled: isMain,
							onClick: function () { toggleFallbackModel( featureKey, model ); },
						}, isMain ? __( '主模型', 'nerv-core' ) + ' · ' + model : model );
					} ) )
				) : null,
				el( TextareaControl, {
					label: cache.length ? __( '备用模型顺序（可手动微调）', 'nerv-core' ) : __( '备用模型', 'nerv-core' ),
					value: featureFallbackText( feature ),
					rows: 4,
					help: cache.length ? __( '上面的模型按钮会自动维护这个列表。顺序从上到下执行。', 'nerv-core' ) : __( '每行一个。主模型失败、429、超时或返回格式错误时会立即切换。', 'nerv-core' ),
					__nextHasNoMarginBottom: true,
					onChange: function ( value ) { setFeatureFallbackText( featureKey, value ); },
				} )
			);
		}

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form' },
				el( 'div', { className: 'nerv-control-panel__title' }, el( 'h3', null, __( 'NERV主题 · AI供应商', 'nerv-core' ) ), el( 'span', { className: 'nerv-control-status-pill nerv-control-status-pill--' + ( status.ready ? 'green' : 'red' ) }, status.label || __( '未配置', 'nerv-core' ) ) ),
				el( 'p', { className: 'nerv-control-form-note' }, __( '可以添加多个 OpenAI 兼容供应商。文本模型和图片模型分开设置，模型列表获取后会缓存到对应供应商。', 'nerv-core' ) ),
				el( 'div', { className: 'nerv-control-ai-usage' },
					el( 'div', null, el( 'span', null, __( '本月', 'nerv-core' ) ), el( 'strong', null, String( usage.month || '-' ) ) ),
					el( 'div', null, el( 'span', null, __( 'AI 操作', 'nerv-core' ) ), el( 'strong', null, String( usage.total || 0 ) ) ),
					el( 'div', null, el( 'span', null, __( '外部调用', 'nerv-core' ) ), el( 'strong', null, String( usage.external || 0 ) ) ),
					el( 'div', null, el( 'span', null, __( '封面 / 要点', 'nerv-core' ) ), el( 'strong', null, String( coverUsage.total || 0 ) + ' / ' + String( keyPointsUsage.total || 0 ) ) )
				),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el( 'div', { className: 'nerv-control-provider-list' },
					form.providers.map( function ( provider, index ) {
						return el( 'div', { className: 'nerv-control-provider-card', key: provider.id },
							el( 'div', { className: 'nerv-control-provider-card__head' }, el( 'strong', null, provider.name || provider.id ), el( Button, { variant: 'secondary', isDestructive: true, disabled: form.providers.length <= 1, onClick: function () { removeProvider( index ); } }, __( '删除', 'nerv-core' ) ) ),
							el( TextControl, { label: __( '供应商名称', 'nerv-core' ), value: provider.name, __next40pxDefaultSize: true, onChange: function ( value ) { updateProvider( index, 'name', value ); } } ),
							el( TextControl, { label: __( 'Base URL', 'nerv-core' ), value: provider.baseUrl, placeholder: 'https://one.dashen.wang', __next40pxDefaultSize: true, onChange: function ( value ) { updateProvider( index, 'baseUrl', value ); } } ),
							el( TextControl, { label: provider.hasApiKey ? __( 'API Key 已保存，输入新值可替换', 'nerv-core' ) : __( 'API Key', 'nerv-core' ), type: 'password', value: provider.apiKey || '', autoComplete: 'new-password', __next40pxDefaultSize: true, onChange: function ( value ) { updateProvider( index, 'apiKey', value ); } } ),
							el( CheckboxControl, { label: __( '启用供应商', 'nerv-core' ), checked: provider.enabled !== false, __nextHasNoMarginBottom: true, onChange: function ( value ) { updateProvider( index, 'enabled', value ); } } ),
							el( 'div', { className: 'nerv-control-model-cache' },
								el( Button, { variant: 'secondary', isBusy: fetchingModels === provider.id, disabled: !! fetchingModels || ! provider.baseUrl, onClick: function () { fetchModels( index ); } }, fetchingModels === provider.id ? __( '正在获取模型...', 'nerv-core' ) : __( '获取模型列表并缓存', 'nerv-core' ) ),
								el( 'p', null, provider.modelCache && provider.modelCache.length ? __( '已缓存模型数量：', 'nerv-core' ) + String( provider.modelCache.length ) + ( provider.modelCacheTime ? ' / ' + provider.modelCacheTime : '' ) : __( '还没有缓存模型列表。', 'nerv-core' ) )
							)
						);
					} ),
					el( Button, { variant: 'secondary', onClick: addProvider }, __( '新增供应商', 'nerv-core' ) )
				),
				el( 'div', { className: 'nerv-control-form-grid' },
					featurePanel( __( '文本模型', 'nerv-core' ), 'textFeature', __( '用于 KEY POINTS、GEO 标题、后续文本类 AI 功能。', 'nerv-core' ) ),
					featurePanel( __( '图片模型', 'nerv-core' ), 'imageFeature', __( '用于 AI 封面生成。', 'nerv-core' ) ),
					el( TextareaControl, { label: __( '封面 Prompt 模板', 'nerv-core' ), value: form.promptTemplate, rows: 4, help: __( '可用占位符：{title}、{subtitle}、{excerpt}、{category}。', 'nerv-core' ), __nextHasNoMarginBottom: true, onChange: function ( value ) { setField( 'promptTemplate', value ); } } )
				),
				el( 'div', { className: 'nerv-control-switches' },
					el( CheckboxControl, { label: __( '生产密钥就绪前使用 AI 试运行', 'nerv-core' ), checked: form.dryRun, __nextHasNoMarginBottom: true, onChange: function ( value ) { setField( 'dryRun', value ); } } ),
					el( CheckboxControl, { label: __( '没有特色图时自动生成封面', 'nerv-core' ), checked: form.autoGenerate, __nextHasNoMarginBottom: true, onChange: function ( value ) { setField( 'autoGenerate', value ); } } ),
					el( CheckboxControl, { label: __( '启用 KEY POINTS AI 生成', 'nerv-core' ), checked: form.keyPointsAuto, __nextHasNoMarginBottom: true, onChange: function ( value ) { setField( 'keyPointsAuto', value ); } } )
				),
				el( 'div', { className: 'nerv-control-actions' }, el( Button, { variant: 'primary', isBusy: saving, disabled: saving, onClick: saveSettings }, saving ? __( '保存中...', 'nerv-core' ) : __( '保存 AI 供应商', 'nerv-core' ) ) )
			)
		);
	}

	function BrandPanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.brand ? data.forms.brand : {};
		const [ form, setForm ] = useState( cloneBrandForm( formData ) );
		const [ saving, setSaving ] = useState( false );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );

		function setField( key, value ) {
			setForm( Object.assign( {}, form, { [ key ]: value } ) );
		}

		function selectMedia( key, title ) {
			if ( ! window.wp || ! window.wp.media ) {
				setError( __( 'WordPress 媒体库不可用。', 'nerv-core' ) );
				return;
			}

			const frame = window.wp.media( {
				title: title,
				button: { text: __( '使用这张图片', 'nerv-core' ) },
				multiple: false,
				library: { type: 'image' },
			} );

			frame.on( 'select', function () {
				const attachment = frame.state().get( 'selection' ).first().toJSON();
				const sizes = attachment.sizes || {};
				const preview = ( sizes.thumbnail && sizes.thumbnail.url ) || ( sizes.medium && sizes.medium.url ) || attachment.url || '';
				setField( key, {
					id: attachment.id || 0,
					url: preview,
					title: attachment.title || '',
					mimeType: attachment.mime || '',
				} );
			} );

			frame.open();
		}

		function clearMedia( key ) {
			setField( key, { id: 0, url: '', title: '', mimeType: '' } );
		}

		function mediaCropControls( baseKey, label ) {
			const fitKey = baseKey + 'Fit';
			const focusXKey = baseKey + 'FocusX';
			const focusYKey = baseKey + 'FocusY';

			return el(
				'div',
				{ className: 'nerv-control-media-crop' },
				el(
					'label',
					{ className: 'nerv-control-select-field' },
					el( 'span', null, label + ' ' + __( '适配', 'nerv-core' ) ),
					el(
						'select',
						{
							value: form[ fitKey ],
							onChange: function ( event ) {
								setField( fitKey, event.target.value );
							},
						},
						el( 'option', { value: 'contain' }, __( '完整显示', 'nerv-core' ) ),
						el( 'option', { value: 'cover' }, __( '裁切铺满', 'nerv-core' ) )
					)
				),
				el(
					'label',
					{ className: 'nerv-control-range-field' },
					el( 'span', null, __( '焦点 X', 'nerv-core' ) + ': ' + String( form[ focusXKey ] ) + '%' ),
					el( 'input', {
						type: 'range',
						min: '0',
						max: '100',
						step: '5',
						value: form[ focusXKey ],
						onChange: function ( event ) {
							setField( focusXKey, parseInt( event.target.value, 10 ) || 0 );
						},
					} )
				),
				el(
					'label',
					{ className: 'nerv-control-range-field' },
					el( 'span', null, __( '焦点 Y', 'nerv-core' ) + ': ' + String( form[ focusYKey ] ) + '%' ),
					el( 'input', {
						type: 'range',
						min: '0',
						max: '100',
						step: '5',
						value: form[ focusYKey ],
						onChange: function ( event ) {
							setField( focusYKey, parseInt( event.target.value, 10 ) || 0 );
						},
					} )
				)
			);
		}

		function pwaIconPreviewTiles() {
			const iconUrl = form.pwaIcon && form.pwaIcon.url ? form.pwaIcon.url : ( formData.pwaIconFallbackUrl || '' );
			const iconStyle = {
				objectFit: form.pwaIconFit,
				objectPosition: String( form.pwaIconFocusX ) + '% ' + String( form.pwaIconFocusY ) + '%',
			};
			const tiles = [
				{ key: 'manifest-small', label: __( 'Manifest 小图标', 'nerv-core' ), size: form.pwaIconSmallSize, note: __( '基础安装', 'nerv-core' ) },
				{ key: 'manifest-large', label: __( 'Manifest 大图标', 'nerv-core' ), size: form.pwaIconLargeSize, note: __( '商店展示', 'nerv-core' ) },
				{ key: 'apple-touch', label: __( 'Apple 触摸图标', 'nerv-core' ), size: form.pwaIconAppleSize, note: __( 'iOS 主屏幕', 'nerv-core' ) },
			];

			return el(
				'div',
				{ className: 'nerv-control-pwa-preview' },
				el( 'span', null, __( 'PWA 图标预览', 'nerv-core' ) ),
				el(
					'div',
					null,
					tiles.map( function ( tile ) {
						return el(
							'figure',
							{ key: tile.key },
							iconUrl ? el( 'img', { src: iconUrl, alt: '', style: iconStyle } ) : el( 'b', null, form.pwaShortName || form.brandMark || 'NERV' ),
							el( 'figcaption', null, el( 'strong', null, tile.label ), el( 'small', null, String( tile.size ) + 'x' + String( tile.size ) + ' · ' + tile.note ) )
						);
					} )
				),
				el( 'p', null, form.pwaIcon && form.pwaIcon.url ? __( '已上传图标，并使用当前裁切设置。', 'nerv-core' ) : __( '当前显示自动生成的 SVG 兜底预览；生产环境建议上传正方形位图图标。', 'nerv-core' ) )
			);
		}

		function saveSettings() {
			setSaving( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.brandPath : '/nerv-core/v1/control-brand',
				method: 'POST',
				data: Object.assign( {}, form, {
					brandLogoId: form.brandLogo && form.brandLogo.id ? form.brandLogo.id : 0,
					pwaIconId: form.pwaIcon && form.pwaIcon.id ? form.pwaIcon.id : 0,
					brandLogoFit: form.brandLogoFit,
					brandLogoFocusX: form.brandLogoFocusX,
					brandLogoFocusY: form.brandLogoFocusY,
					pwaIconFit: form.pwaIconFit,
					pwaIconFocusX: form.pwaIconFocusX,
					pwaIconFocusY: form.pwaIconFocusY,
					pwaIconSmallSize: form.pwaIconSmallSize,
					pwaIconLargeSize: form.pwaIconLargeSize,
					pwaIconAppleSize: form.pwaIconAppleSize,
					fontCssUrl: form.fontCssUrl,
					fontBodyFamily: form.fontBodyFamily,
					fontHeadingFamily: form.fontHeadingFamily,
					fontMonoFamily: form.fontMonoFamily,
				} ),
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( cloneBrandForm( response.dashboard.forms.brand ) );
					}
					setNotice( response.message || __( '品牌设置已保存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( '品牌设置保存失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form nerv-control-panel--brand' },
				el(
					'div',
					{ className: 'nerv-control-panel__title' },
					el( 'h3', null, __( 'NERV主题 · 品牌', 'nerv-core' ) ),
					el( 'span', { className: 'nerv-control-status-pill nerv-control-status-pill--green' }, __( '品牌设置已启用', 'nerv-core' ) )
				),
				el( 'p', { className: 'nerv-control-form-note' }, __( '设置首屏品牌标识、页头时钟标签和终端外壳使用的 PWA 安装信息。', 'nerv-core' ) ),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'nerv-control-form-grid' },
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '页头品牌', 'nerv-core' ) ),
						el( TextControl, {
							label: __( '品牌标题', 'nerv-core' ),
							value: form.brandTitle,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'brandTitle', value );
							},
						} ),
						el( TextControl, {
							label: __( '品牌副标题', 'nerv-core' ),
							value: form.brandSubtitle,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'brandSubtitle', value );
							},
						} ),
						el( TextControl, {
							label: __( '中心标识', 'nerv-core' ),
							value: form.brandMark,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'brandMark', value );
							},
						} ),
						el(
							'div',
							{ className: 'nerv-control-media-field' },
							el( 'span', null, __( '页头 Logo 图片', 'nerv-core' ) ),
							form.brandLogo && form.brandLogo.url ? el( 'img', { src: form.brandLogo.url, alt: '', style: { objectFit: form.brandLogoFit, objectPosition: String( form.brandLogoFocusX ) + '% ' + String( form.brandLogoFocusY ) + '%' } } ) : el( 'em', null, __( '当前使用文字标识兜底。', 'nerv-core' ) ),
							el(
								'div',
								null,
								el(
									Button,
									{ variant: 'secondary', onClick: function () { selectMedia( 'brandLogo', __( '选择页头 Logo', 'nerv-core' ) ); } },
									form.brandLogo && form.brandLogo.id ? __( '替换 Logo', 'nerv-core' ) : __( '选择 Logo', 'nerv-core' )
								),
								form.brandLogo && form.brandLogo.id ? el(
									Button,
									{ variant: 'tertiary', onClick: function () { clearMedia( 'brandLogo' ); } },
									__( '清除', 'nerv-core' )
								) : null
							),
							mediaCropControls( 'brandLogo', __( 'Logo', 'nerv-core' ) )
						)
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '时钟条', 'nerv-core' ) ),
						el( TextControl, {
							label: __( '时钟标签', 'nerv-core' ),
							value: form.clockLabel,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'clockLabel', value );
							},
						} ),
						el( TextControl, {
							label: __( '时区徽标', 'nerv-core' ),
							value: form.clockTimezone,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'clockTimezone', value );
							},
						} ),
						el( TextControl, {
							label: __( '活动徽标', 'nerv-core' ),
							value: form.activeLabel,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'activeLabel', value );
							},
						} )
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( 'PWA 身份', 'nerv-core' ) ),
						el( TextControl, {
							label: __( '应用名称', 'nerv-core' ),
							value: form.pwaName,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'pwaName', value );
							},
						} ),
						el( TextControl, {
							label: __( '短名称', 'nerv-core' ),
							value: form.pwaShortName,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'pwaShortName', value );
							},
						} ),
						el( TextControl, {
							label: __( '主题色', 'nerv-core' ),
							type: 'color',
							value: form.themeColor,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'themeColor', value );
							},
						} ),
						el(
							'div',
							{ className: 'nerv-control-media-field' },
							el( 'span', null, __( 'PWA 图标图片', 'nerv-core' ) ),
							form.pwaIcon && form.pwaIcon.url ? el( 'img', { src: form.pwaIcon.url, alt: '', style: { objectFit: form.pwaIconFit, objectPosition: String( form.pwaIconFocusX ) + '% ' + String( form.pwaIconFocusY ) + '%' } } ) : el( 'em', null, __( '当前使用自动生成的 SVG 兜底图标。', 'nerv-core' ) ),
							el(
								'div',
								null,
								el(
									Button,
									{ variant: 'secondary', onClick: function () { selectMedia( 'pwaIcon', __( '选择 PWA 图标', 'nerv-core' ) ); } },
									form.pwaIcon && form.pwaIcon.id ? __( '替换图标', 'nerv-core' ) : __( '选择图标', 'nerv-core' )
								),
								form.pwaIcon && form.pwaIcon.id ? el(
									Button,
									{ variant: 'tertiary', onClick: function () { clearMedia( 'pwaIcon' ); } },
									__( '清除', 'nerv-core' )
								) : null
							),
							mediaCropControls( 'pwaIcon', __( '图标', 'nerv-core' ) )
						),
						el(
							'div',
							{ className: 'nerv-control-icon-size-grid' },
							el( TextControl, {
								label: __( 'Manifest small icon', 'nerv-core' ),
								type: 'number',
								min: 64,
								max: 1024,
								value: String( form.pwaIconSmallSize ),
								help: __( 'PWA 常用基础尺寸为 192px。', 'nerv-core' ),
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									setField( 'pwaIconSmallSize', Math.min( 1024, Math.max( 64, parseInt( value, 10 ) || 192 ) ) );
								},
							} ),
							el( TextControl, {
								label: __( 'Manifest large icon', 'nerv-core' ),
								type: 'number',
								min: 64,
								max: 1024,
								value: String( form.pwaIconLargeSize ),
								help: __( '安装图标常用尺寸为 512px。', 'nerv-core' ),
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									setField( 'pwaIconLargeSize', Math.min( 1024, Math.max( 64, parseInt( value, 10 ) || 512 ) ) );
								},
							} ),
							el( TextControl, {
								label: __( 'Apple touch icon', 'nerv-core' ),
								type: 'number',
								min: 64,
								max: 1024,
								value: String( form.pwaIconAppleSize ),
								help: __( 'iOS 常用尺寸为 180px。', 'nerv-core' ),
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									setField( 'pwaIconAppleSize', Math.min( 1024, Math.max( 64, parseInt( value, 10 ) || 180 ) ) );
								},
							} )
						)
					),
					el(
						'div',
						{ className: 'nerv-control-brand-preview' },
						el( 'span', null, __( '页头预览', 'nerv-core' ) ),
						el( 'strong', null, form.brandTitle || __( 'NERV Terminal', 'nerv-core' ) ),
						el( 'em', null, form.brandSubtitle || __( '个人作品集', 'nerv-core' ) ),
						form.brandLogo && form.brandLogo.url ? el( 'img', { src: form.brandLogo.url, alt: '', style: { objectFit: form.brandLogoFit, objectPosition: String( form.brandLogoFocusX ) + '% ' + String( form.brandLogoFocusY ) + '%' } } ) : el( 'b', null, form.brandMark || 'NERV' ),
						el(
							'small',
							null,
							( form.clockLabel || __( '系统时间', 'nerv-core' ) ) + ' / --:--:-- / ' + ( form.clockTimezone || 'JST' ) + ' / ' + ( form.activeLabel || 'ACTIVE' )
						)
					),
					pwaIconPreviewTiles()
					,
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '自定义字体', 'nerv-core' ) ),
						el( TextControl, {
							label: __( '字体 CSS URL', 'nerv-core' ),
							value: form.fontCssUrl,
							placeholder: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap',
							help: __( '可填写 Google Fonts、Bunny Fonts 或自托管字体 CSS 地址。', 'nerv-core' ),
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'fontCssUrl', value );
							},
						} ),
						el( TextControl, {
							label: __( '正文字体栈', 'nerv-core' ),
							value: form.fontBodyFamily,
							placeholder: '"Inter", "Noto Sans SC", sans-serif',
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'fontBodyFamily', value );
							},
						} ),
						el( TextControl, {
							label: __( '标题字体栈', 'nerv-core' ),
							value: form.fontHeadingFamily,
							placeholder: '"Inter", "Noto Sans SC", sans-serif',
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'fontHeadingFamily', value );
							},
						} ),
						el( TextControl, {
							label: __( '等宽/界面字体栈', 'nerv-core' ),
							value: form.fontMonoFamily,
							placeholder: '"JetBrains Mono", "Noto Sans SC", monospace',
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'fontMonoFamily', value );
							},
						} )
					)
				),
				el(
					'div',
					{ className: 'nerv-control-resource-grid' },
					el(
						'a',
						{ href: formData.homeUrl || '#', target: '_blank', rel: 'noreferrer' },
						el( 'span', null, __( 'home', 'nerv-core' ) ),
						el( 'strong', null, form.brandTitle || data.site.name || 'NERV' )
					),
					el(
						'a',
						{ href: formData.manifestUrl || '#', target: '_blank', rel: 'noreferrer' },
						el( 'span', null, __( 'manifest', 'nerv-core' ) ),
						el( 'strong', null, ( form.pwaShortName || 'NERV' ) + ' · ' + String( form.pwaIconSmallSize ) + '/' + String( form.pwaIconLargeSize ) + '/' + String( form.pwaIconAppleSize ) )
					)
				),
				el(
					'div',
					{ className: 'nerv-control-actions' },
					el(
						Button,
						{ variant: 'primary', isBusy: saving, disabled: saving, onClick: saveSettings },
						saving ? __( '保存中...', 'nerv-core' ) : __( '保存品牌设置', 'nerv-core' )
					)
				)
			)
		);
	}

	function SeoPanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.seo ? data.forms.seo : {};
		const [ form, setForm ] = useState( cloneSeoForm( formData ) );
		const [ saving, setSaving ] = useState( false );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );

		function setField( key, value ) {
			setForm( Object.assign( {}, form, { [ key ]: value } ) );
		}

		function selectMedia() {
			if ( ! window.wp || ! window.wp.media ) {
				setError( __( 'WordPress 媒体库不可用。', 'nerv-core' ) );
				return;
			}

			const frame = window.wp.media( {
				title: __( '选择默认社交图', 'nerv-core' ),
				button: { text: __( '使用这张图片', 'nerv-core' ) },
				multiple: false,
				library: { type: 'image' },
			} );

			frame.on( 'select', function () {
				const attachment = frame.state().get( 'selection' ).first().toJSON();
				const sizes = attachment.sizes || {};
				setField( 'defaultOgImage', {
					id: attachment.id || 0,
					url: ( sizes.thumbnail && sizes.thumbnail.url ) || ( sizes.medium && sizes.medium.url ) || attachment.url || '',
					title: attachment.title || '',
					mimeType: attachment.mime || '',
				} );
			} );

			frame.open();
		}

		function saveSettings() {
			setSaving( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.seoPath : '/nerv-core/v1/control-seo',
				method: 'POST',
				data: Object.assign( {}, form, {
					defaultOgImageId: form.defaultOgImage && form.defaultOgImage.id ? form.defaultOgImage.id : 0,
				} ),
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( cloneSeoForm( response.dashboard.forms.seo ) );
					}
					setNotice( response.message || __( 'SEO 设置已保存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( 'SEO 设置保存失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form nerv-control-panel--seo' },
				el(
					'div',
					{ className: 'nerv-control-panel__title' },
					el( 'h3', null, __( 'NERV主题 · SEO', 'nerv-core' ) ),
					el(
						'span',
						{ className: 'nerv-control-status-pill nerv-control-status-pill--' + ( form.enabled ? 'green' : 'red' ) },
						form.enabled ? __( '主题 Meta 已启用', 'nerv-core' ) : __( '主题 Meta 已停用', 'nerv-core' )
					)
				),
				el( 'p', { className: 'nerv-control-form-note' }, __( '设置主题生成的描述、Open Graph 图片、SEO 插件接管和 Markdown 镜像索引策略。', 'nerv-core' ) ),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'nerv-control-form-grid' },
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( 'Meta 输出', 'nerv-core' ) ),
						el( CheckboxControl, {
							label: __( '启用主题生成的 SEO Meta 标签', 'nerv-core' ),
							checked: form.enabled,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								setField( 'enabled', value );
							},
						} ),
						el( CheckboxControl, {
							label: __( '检测到 SEO 插件时交给插件输出 Meta', 'nerv-core' ),
							checked: form.deferToSeoPlugin,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								setField( 'deferToSeoPlugin', value );
							},
						} ),
						el( 'p', { className: 'nerv-control-mini' }, formData.detectedSeoPlugin ? __( '已检测到 SEO 插件。', 'nerv-core' ) : __( '未检测到支持的 SEO 插件。', 'nerv-core' ) )
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '站点描述', 'nerv-core' ) ),
						el( TextareaControl, {
							label: __( '默认描述', 'nerv-core' ),
							value: form.siteDescription,
							rows: 4,
							help: __( '当 WordPress 副标题为空且当前页面没有摘要时使用。', 'nerv-core' ),
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								setField( 'siteDescription', value );
							},
						} )
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '社交预览图', 'nerv-core' ) ),
						el(
							'div',
							{ className: 'nerv-control-media-field' },
							el( 'span', null, __( '默认 Open Graph 图片', 'nerv-core' ) ),
							form.defaultOgImage && form.defaultOgImage.url ? el( 'img', { src: form.defaultOgImage.url, alt: '' } ) : el( 'em', null, __( '优先使用文章封面，否则使用应用图标兜底。', 'nerv-core' ) ),
							el(
								'div',
								null,
								el(
									Button,
									{ variant: 'secondary', onClick: selectMedia },
									form.defaultOgImage && form.defaultOgImage.id ? __( '替换图片', 'nerv-core' ) : __( '选择图片', 'nerv-core' )
								),
								form.defaultOgImage && form.defaultOgImage.id ? el(
									Button,
									{ variant: 'tertiary', onClick: function () { setField( 'defaultOgImage', { id: 0, url: '', title: '' } ); } },
									__( '清除', 'nerv-core' )
								) : null
							)
						)
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( 'Markdown 镜像', 'nerv-core' ) ),
						el( CheckboxControl, {
							label: __( 'Add X-Robots-Tag: noindex to .md mirrors', 'nerv-core' ),
							checked: form.noindexMarkdown,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								setField( 'noindexMarkdown', value );
							},
						} ),
						el( 'p', { className: 'nerv-control-mini' }, __( ' canonical 文章 URL 保持可索引；Markdown 镜像保持机器可读，但不与正文页面竞争搜索排名。', 'nerv-core' ) )
					)
				),
				el(
					'div',
					{ className: 'nerv-control-actions' },
					el(
						Button,
						{ variant: 'primary', isBusy: saving, disabled: saving, onClick: saveSettings },
						saving ? __( '保存中...', 'nerv-core' ) : __( '保存 SEO 设置', 'nerv-core' )
					)
				)
			)
		);
	}

	function ArticlesPanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.articles ? data.forms.articles : {};
		const categories = formData.categories || [];
		const [ form, setForm ] = useState( cloneArticlesForm( formData ) );
		const [ saving, setSaving ] = useState( false );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );

		function setField( key, value ) {
			setForm( Object.assign( {}, form, { [ key ]: value } ) );
		}

		function toggleCategory( id, checked ) {
			const current = form.excludedCategories || [];
			const next = checked
				? current.concat( [ id ] ).filter( function ( value, index, values ) {
						return values.indexOf( value ) === index;
				  } )
				: current.filter( function ( value ) {
						return value !== id;
				  } );

			setField( 'excludedCategories', next );
		}

		function saveSettings() {
			setSaving( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.articlesPath : '/nerv-core/v1/control-articles',
				method: 'POST',
				data: form,
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( cloneArticlesForm( response.dashboard.forms.articles ) );
					}
					setNotice( response.message || __( '文章设置已保存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( '文章设置保存失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form nerv-control-panel--articles' },
				el(
					'div',
					{ className: 'nerv-control-panel__title' },
					el( 'h3', null, __( 'NERV主题 · 文章', 'nerv-core' ) ),
					el(
						'span',
						{ className: 'nerv-control-status-pill nerv-control-status-pill--' + ( form.enabled ? 'green' : 'red' ) },
						form.enabled ? __( '相关文章引擎已启用', 'nerv-core' ) : __( '相关文章引擎已停用', 'nerv-core' )
					)
				),
				el( 'p', { className: 'nerv-control-form-note' }, __( '设置文章语义链接、可见相关文章面板、缓存行为和 GEO 隐藏相关链接。', 'nerv-core' ) ),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'nerv-control-form-grid' },
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '相关文章', 'nerv-core' ) ),
						el( CheckboxControl, {
							label: __( '启用相关文章面板和 GEO 相关链接', 'nerv-core' ),
							checked: form.enabled,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								setField( 'enabled', value );
							},
						} ),
						el( TextControl, {
							label: __( '面板标题', 'nerv-core' ),
							value: form.title,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'title', value );
							},
						} ),
						el( TextControl, {
							label: __( '条目数量', 'nerv-core' ),
							type: 'number',
							min: 1,
							max: 12,
							value: String( form.limit ),
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'limit', value );
							},
						} )
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '算法权重', 'nerv-core' ) ),
						el( TextControl, {
							label: __( '同分类', 'nerv-core' ),
							type: 'number',
							min: 0,
							max: 20,
							value: String( form.categoryWeight ),
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'categoryWeight', value );
							},
						} ),
						el( TextControl, {
							label: __( '同标签', 'nerv-core' ),
							type: 'number',
							min: 0,
							max: 20,
							value: String( form.tagWeight ),
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'tagWeight', value );
							},
						} ),
						el( TextControl, {
							label: __( '近期文章', 'nerv-core' ),
							type: 'number',
							min: 0,
							max: 20,
							value: String( form.recentWeight ),
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'recentWeight', value );
							},
						} )
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '时间窗口', 'nerv-core' ) ),
						el( TextControl, {
							label: __( '近期天数', 'nerv-core' ),
							type: 'number',
							min: 1,
							max: 3650,
							value: String( form.recentDays ),
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'recentDays', value );
							},
						} ),
						el( TextControl, {
							label: __( '缓存小时', 'nerv-core' ),
							type: 'number',
							min: 1,
							max: 168,
							value: String( form.cacheHours ),
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								setField( 'cacheHours', value );
							},
						} ),
						el( 'p', { className: 'nerv-control-mini' }, __( '保存这些设置会刷新相关文章缓存。', 'nerv-core' ) )
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '文章库存', 'nerv-core' ) ),
						el( 'p', { className: 'nerv-control-mini' }, String( formData.postCount || 0 ) + ' published posts' ),
						el( 'p', { className: 'nerv-control-mini' }, String( categories.length ) + ' categories available' ),
						formData.previewPostUrl
							? el( 'a', { className: 'nerv-control-inline-link', href: formData.previewPostUrl, target: '_blank', rel: 'noreferrer' }, __( '预览最新文章', 'nerv-core' ) )
							: null
					)
				),
				el(
					'div',
					{ className: 'nerv-control-category-grid' },
					categories.length
						? categories.map( function ( category ) {
								return el(
									'label',
									{ className: 'nerv-control-category', key: category.id },
									el( 'input', {
										type: 'checkbox',
										checked: ( form.excludedCategories || [] ).indexOf( category.id ) !== -1,
										onChange: function ( event ) {
											toggleCategory( category.id, event.target.checked );
										},
									} ),
									el( 'strong', null, category.name ),
									el( 'span', null, category.slug ),
									el( 'em', null, String( category.count || 0 ) + ' posts' )
								);
						  } )
						: el( 'p', { className: 'nerv-control-empty' }, __( '暂无可用分类。', 'nerv-core' ) )
				),
				el( 'p', { className: 'nerv-control-mini nerv-control-mini--spaced' }, __( '勾选的分类会从相关文章评分和兜底查询中排除。', 'nerv-core' ) ),
				el(
					'div',
					{ className: 'nerv-control-actions' },
					el(
						Button,
						{ variant: 'primary', isBusy: saving, disabled: saving, onClick: saveSettings },
						saving ? __( '保存中...', 'nerv-core' ) : __( '保存文章设置', 'nerv-core' )
					)
				)
			)
		);
	}

	function GeoPanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.geo ? data.forms.geo : {};
		const [ form, setForm ] = useState( cloneGeoForm( formData ) );
		const [ saving, setSaving ] = useState( false );
		const [ runningAction, setRunningAction ] = useState( '' );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );
		const bots = formData.crawler && formData.crawler.bots ? formData.crawler.bots : [];
		const resources = formData.resources || {};
		const geoTitle = formData.geoTitle || {};
		const titleCandidates = geoTitle.candidates || [];
		const slugBatch = geoTitle.batch || {};
		const [ slugBatchSettings, setSlugBatchSettings ] = useState( {
			batchSize: slugBatch.batchSize || 5,
			concurrency: slugBatch.concurrency || 2,
		} );

		function updateIndexNow( key, value ) {
			setForm( Object.assign( {}, form, { indexnow: Object.assign( {}, form.indexnow, { [ key ]: value } ) } ) );
		}

		function updateCrawler( key, value ) {
			setForm( Object.assign( {}, form, { crawler: Object.assign( {}, form.crawler, { [ key ]: value } ) } ) );
		}

		function updateBot( key, value ) {
			updateCrawler( 'bots', Object.assign( {}, form.crawler.bots, { [ key ]: value } ) );
		}

		function saveSettings() {
			setSaving( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.geoPath : '/nerv-core/v1/control-geo',
				method: 'POST',
				data: form,
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( cloneGeoForm( response.dashboard.forms.geo ) );
					}
					setNotice( response.message || __( 'GEO 设置已保存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( 'GEO 设置保存失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		function runGeoAction( action, path, fallbackMessage, payload ) {
			setRunningAction( action );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: path,
				method: 'POST',
				data: payload || {},
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( cloneGeoForm( response.dashboard.forms.geo ) );
					}
					setNotice( response.message || fallbackMessage );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( 'GEO 操作未能完成。', 'nerv-core' ) );
				} )
				.finally( function () {
					setRunningAction( '' );
				} );
		}

		function runSlugBatch( mode ) {
			runGeoAction(
				'geo-slug-' + mode,
				window.nervCoreControl ? window.nervCoreControl.geoSlugBatchPath : '/nerv-core/v1/control-geo-slug-batch',
				__( 'GEO slug 挂机任务已更新。', 'nerv-core' ),
				{ mode: mode, batchSize: slugBatchSettings.batchSize, concurrency: slugBatchSettings.concurrency }
			);
		}

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form nerv-control-panel--geo' },
				el(
					'div',
					{ className: 'nerv-control-panel__title' },
					el( 'h3', null, __( 'NERV主题 · GEO', 'nerv-core' ) ),
					el(
						'span',
						{ className: 'nerv-control-status-pill nerv-control-status-pill--green' },
						__( '机器可读资源已启用', 'nerv-core' )
					)
				),
				el( 'p', { className: 'nerv-control-form-note' }, __( '管理 AI 可读发布层：IndexNow 推送、爬虫可见性、robots 策略和机器可读资源状态。', 'nerv-core' ) ),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'nerv-control-form-grid' },
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( 'IndexNow', 'nerv-core' ) ),
						el( CheckboxControl, {
							label: __( '发布/更新时启用 IndexNow 推送', 'nerv-core' ),
							checked: form.indexnow.enabled,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								updateIndexNow( 'enabled', value );
							},
						} ),
						el( TextControl, {
							label: __( '密钥', 'nerv-core' ),
							value: form.indexnow.key,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								updateIndexNow( 'key', value );
							},
						} ),
						el( TextControl, {
							label: __( '端点', 'nerv-core' ),
							value: form.indexnow.endpoint,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								updateIndexNow( 'endpoint', value );
							},
						} ),
						el( CheckboxControl, {
							label: __( '仅试运行：记录日志但不向外提交', 'nerv-core' ),
							checked: form.indexnow.dryRun,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								updateIndexNow( 'dryRun', value );
							},
						} ),
						formData.indexnow && formData.indexnow.keyUrl ? el( 'p', { className: 'nerv-control-mini' }, formData.indexnow.keyUrl ) : null
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( 'AI 爬虫监控', 'nerv-core' ) ),
						el( CheckboxControl, {
							label: __( '启用 AI 爬虫监控和 robots 策略输出', 'nerv-core' ),
							checked: form.crawler.enabled,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								updateCrawler( 'enabled', value );
							},
						} ),
						el( TextControl, {
							label: __( '数据保留天数', 'nerv-core' ),
							type: 'number',
							min: 1,
							max: 365,
							value: String( form.crawler.retentionDays ),
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								updateCrawler( 'retentionDays', value );
							},
						} ),
						el( 'p', { className: 'nerv-control-mini' }, __( '勾选的机器人会被监控并在 robots.txt 中放行；未勾选的会被阻止。', 'nerv-core' ) )
					)
				),
				el(
					'div',
					{ className: 'nerv-control-bot-grid' },
					bots.map( function ( bot ) {
						return el(
							'label',
							{ className: 'nerv-control-bot', key: bot.key },
							el( 'input', {
								type: 'checkbox',
								checked: !! form.crawler.bots[ bot.key ],
								onChange: function ( event ) {
									updateBot( bot.key, event.target.checked );
								},
							} ),
							el( 'strong', null, bot.label ),
							el( 'span', null, bot.pattern ),
							el( 'em', null, String( bot.window || 0 ) + ' / 7D · ' + String( bot.total || 0 ) + ' total' )
						);
					} )
				),
				el(
					'div',
					{ className: 'nerv-control-resource-grid' },
					[ 'llms', 'llmsFull', 'jsonFeed', 'aiPolicy' ].map( function ( key ) {
						return el(
							'a',
							{ href: resources[ key ] || '#', key: key, target: '_blank', rel: 'noreferrer' },
							el( 'span', null, key ),
							el( 'strong', null, key === 'aiPolicy' && ! resources.policyReady ? __( '缺失', 'nerv-core' ) : __( '在线', 'nerv-core' ) )
						);
					} )
				),
				el(
					'div',
					{ className: 'nerv-control-command-row' },
					el(
						'div',
						null,
						el( 'strong', null, __( 'GEO标题 slug 建议', 'nerv-core' ) ),
						el( 'span', null, titleCandidates.length ? __( '发现需要优化的文章 slug。生成建议后进入文章 meta，等待审阅。', 'nerv-core' ) : __( '没有发现需要优化的文章 slug。', 'nerv-core' ) )
					),
					el(
						Button,
						{
							variant: 'secondary',
							isBusy: 'geo-title' === runningAction,
							disabled: !! runningAction || saving || ! titleCandidates.length,
							onClick: function () {
								runGeoAction(
									'geo-title',
									window.nervCoreControl ? window.nervCoreControl.geoTitlePath : '/nerv-core/v1/control-geo-title-suggest',
									__( 'GEO标题 slug 建议已生成。', 'nerv-core' )
								);
							},
						},
						__( '生成下一篇建议', 'nerv-core' )
					)
				),
					titleCandidates.length ? el(
						'ul',
						{ className: 'nerv-control-geo-title-list' },
					titleCandidates.slice( 0, 5 ).map( function ( row ) {
						return el(
							'li',
							{ key: row.id },
							el( 'strong', null, row.title || '' ),
							el( 'span', null, ( row.reason || '' ) + ' / ' + ( row.slug || '' ) )
							);
						} )
					) : null,
					el(
						'div',
						{ className: 'nerv-control-command-row nerv-control-command-row--stack' },
						el(
							'div',
							null,
							el( 'strong', null, __( 'GEO slug 挂机模式', 'nerv-core' ) ),
							el( 'span', null, __( '自动批量处理不适合 SEO/GEO 的旧文章链接，支持失败重试与批次推进。', 'nerv-core' ) ),
							el( 'small', null, String( slugBatch.processed || 0 ) + '/' + String( slugBatch.total || 0 ) + ' · ' + ( slugBatch.status || 'idle' ) + ' · changed ' + String( slugBatch.changed || 0 ) + ' · failed ' + String( slugBatch.failed || 0 ) )
						),
						el(
							'div',
							{ className: 'nerv-control-action-pair nerv-control-action-pair--inputs' },
							el( TextControl, {
								label: __( '每批数量', 'nerv-core' ),
								type: 'number',
								min: 1,
								max: 25,
								value: String( slugBatchSettings.batchSize ),
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									setSlugBatchSettings( Object.assign( {}, slugBatchSettings, { batchSize: Math.max( 1, Math.min( 25, parseInt( value, 10 ) || 1 ) ) } ) );
								},
							} ),
							el( TextControl, {
								label: __( '并发线程', 'nerv-core' ),
								type: 'number',
								min: 1,
								max: 8,
								value: String( slugBatchSettings.concurrency ),
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									setSlugBatchSettings( Object.assign( {}, slugBatchSettings, { concurrency: Math.max( 1, Math.min( 8, parseInt( value, 10 ) || 1 ) ) } ) );
								},
							} )
						),
						el(
							'div',
							{ className: 'nerv-control-action-pair' },
							el( Button, { variant: 'primary', isBusy: 'geo-slug-start' === runningAction, disabled: !! runningAction || saving, onClick: function () { runSlugBatch( 'start' ); } }, __( '启动挂机改链接', 'nerv-core' ) ),
							el( Button, { variant: 'secondary', isBusy: 'geo-slug-tick' === runningAction, disabled: !! runningAction || saving || ! slugBatch.total || 'paused' === slugBatch.status, onClick: function () { runSlugBatch( 'tick' ); } }, __( '立即跑一批', 'nerv-core' ) ),
							el( Button, { variant: 'secondary', isBusy: 'geo-slug-pause' === runningAction, disabled: !! runningAction || saving || 'running' !== slugBatch.status, onClick: function () { runSlugBatch( 'pause' ); } }, __( '暂停', 'nerv-core' ) ),
							el( Button, { variant: 'secondary', isBusy: 'geo-slug-resume' === runningAction, disabled: !! runningAction || saving || 'paused' !== slugBatch.status, onClick: function () { runSlugBatch( 'resume' ); } }, __( '恢复', 'nerv-core' ) ),
							el( Button, { variant: 'tertiary', isBusy: 'geo-slug-stop' === runningAction, disabled: !! runningAction || saving || [ 'running', 'paused' ].indexOf( slugBatch.status ) < 0, onClick: function () { runSlugBatch( 'stop' ); } }, __( '停止', 'nerv-core' ) )
						)
					),
					slugBatch.log && slugBatch.log.length ? el(
						'ul',
						{ className: 'nerv-control-geo-title-list' },
						slugBatch.log.slice( 0, 6 ).map( function ( row, index ) {
							return el( 'li', { key: 'slug-log-' + index }, el( 'strong', null, row.state || '' ), el( 'span', null, row.message || '' ) );
						} )
					) : null,
					el(
					'div',
					{ className: 'nerv-control-command-row' },
					el(
						'div',
						null,
						el( 'strong', null, __( '推荐 GEO 设置', 'nerv-core' ) ),
						el( 'span', null, __( '应用上线安全默认值：IndexNow 试运行、AI 爬虫可见、AI 政策页和刷新 Markdown 镜像。', 'nerv-core' ) )
					),
					el(
						Button,
						{
							variant: 'primary',
							isBusy: 'geo-defaults' === runningAction,
							disabled: !! runningAction || saving,
							onClick: function () {
								runGeoAction(
									'geo-defaults',
									window.nervCoreControl ? window.nervCoreControl.toolsActionPath : '/nerv-core/v1/control-tools-action',
									__( '推荐 GEO 设置已完成。', 'nerv-core' ),
									{ toolAction: 'apply_geo_defaults' }
								);
							},
						},
						__( '应用 GEO 默认设置', 'nerv-core' )
					)
				),
				el(
					'div',
					{ className: 'nerv-control-command-row' },
					el(
						'div',
						null,
						el( 'strong', null, __( 'AI 使用政策', 'nerv-core' ) ),
						el( 'span', null, resources.policyReady ? __( '已发布，并已从机器可读资源链接。', 'nerv-core' ) : __( '缺失；上线前请生成政策页。', 'nerv-core' ) )
					),
					el(
						Button,
						{
							variant: 'secondary',
							isBusy: 'policy' === runningAction,
							disabled: !! runningAction || saving,
							onClick: function () {
								runGeoAction(
									'policy',
									window.nervCoreControl ? window.nervCoreControl.aiPolicyPath : '/nerv-core/v1/control-ai-policy-generate',
									__( 'AI usage policy page generated.', 'nerv-core' )
								);
							},
						},
						resources.policyReady ? __( '刷新 AI 政策页', 'nerv-core' ) : __( '生成 AI 政策页', 'nerv-core' )
					)
				),
				el(
					'div',
					{ className: 'nerv-control-command-row' },
					el(
						'div',
						null,
						el( 'strong', null, __( 'IndexNow TEST', 'nerv-core' ) ),
						el( 'span', null, __( '有 AI 政策 URL 时提交；本地站点保持试运行。', 'nerv-core' ) )
					),
					el(
						Button,
						{
							variant: 'secondary',
							isBusy: 'indexnow' === runningAction,
							disabled: !! runningAction || saving,
							onClick: function () {
								runGeoAction(
									'indexnow',
									window.nervCoreControl ? window.nervCoreControl.indexnowPath : '/nerv-core/v1/control-indexnow-test',
									__( 'IndexNow 测试已完成。', 'nerv-core' )
								);
							},
						},
						__( '运行 IndexNow 测试', 'nerv-core' )
					)
				),
				el(
					'div',
					{ className: 'nerv-control-actions' },
					el(
						Button,
						{ variant: 'primary', isBusy: saving, disabled: saving, onClick: saveSettings },
						saving ? __( '保存中...', 'nerv-core' ) : __( '保存 GEO 设置', 'nerv-core' )
					)
				)
			)
		);
	}

	function MobilePanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.mobile ? data.forms.mobile : {};
		const icons = formData.icons || [];
		const targets = formData.targets || [];
		const [ form, setForm ] = useState( cloneMobileForm( formData ) );
		const [ saving, setSaving ] = useState( false );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );

		function setField( key, value ) {
			setForm( Object.assign( {}, form, { [ key ]: value } ) );
		}

		function updateTab( index, key, value ) {
			const tabs = form.tabs.map( function ( tab, tabIndex ) {
				return tabIndex === index ? Object.assign( {}, tab, { [ key ]: value } ) : tab;
			} );
			setField( 'tabs', tabs );
		}

		function updateSection( key, value ) {
			setField( 'moreSections', Object.assign( {}, form.moreSections, { [ key ]: value } ) );
		}

		function saveSettings() {
			setSaving( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.mobilePath : '/nerv-core/v1/control-mobile',
				method: 'POST',
				data: form,
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( cloneMobileForm( response.dashboard.forms.mobile ) );
					}
					setNotice( response.message || __( '移动端设置已保存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( '移动端设置保存失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form nerv-control-panel--mobile' },
				el(
					'div',
					{ className: 'nerv-control-panel__title' },
					el( 'h3', null, __( 'NERV主题 · 移动端', 'nerv-core' ) ),
					el( 'span', { className: 'nerv-control-status-pill nerv-control-status-pill--' + ( form.enabled ? 'green' : 'red' ) }, form.enabled ? __( '应用外壳已启用', 'nerv-core' ) : __( '应用外壳已停用', 'nerv-core' ) )
				),
				el( 'p', { className: 'nerv-control-form-note' }, __( '设置固定移动端 Tab Bar、MORE 页面内容和应用式导航；建议启用 3 到 5 个适合拇指操作的标签。', 'nerv-core' ) ),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'nerv-control-switches' },
					el( CheckboxControl, {
						label: __( '启用移动端应用外壳和固定 Tab Bar', 'nerv-core' ),
						checked: form.enabled,
						__nextHasNoMarginBottom: true,
						onChange: function ( value ) {
							setField( 'enabled', value );
						},
					} ),
					el( CheckboxControl, {
						label: __( '启用 MORE 标签路由', 'nerv-core' ),
						checked: form.moreEnabled,
						__nextHasNoMarginBottom: true,
						onChange: function ( value ) {
							setField( 'moreEnabled', value );
						},
					} )
				),
				el(
					'div',
					{ className: 'nerv-control-mobile-tabs-editor' },
					form.tabs.map( function ( tab, index ) {
						return el(
							'div',
							{ className: 'nerv-control-mobile-tab-row', key: tab.id || index },
							el( 'strong', null, String( index + 1 ).padStart( 2, '0' ) ),
							el( CheckboxControl, {
								label: __( '已启用', 'nerv-core' ),
								checked: !! tab.enabled,
								__nextHasNoMarginBottom: true,
								onChange: function ( value ) {
									updateTab( index, 'enabled', value );
								},
							} ),
							el( TextControl, {
								label: __( '标签', 'nerv-core' ),
								value: tab.label || '',
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									updateTab( index, 'label', value );
								},
							} ),
							el(
								'label',
								{ className: 'nerv-control-select-field' },
								el( 'span', null, __( '图标', 'nerv-core' ) ),
								el(
									'select',
									{
										value: tab.icon || 'grid',
										onChange: function ( event ) {
											updateTab( index, 'icon', event.target.value );
										},
									},
									icons.map( function ( icon ) {
										return el( 'option', { value: icon, key: icon }, icon );
									} )
								)
							),
							el(
								'label',
								{ className: 'nerv-control-select-field' },
								el( 'span', null, __( '目标', 'nerv-core' ) ),
								el(
									'select',
									{
										value: tab.target || 'custom',
										onChange: function ( event ) {
											updateTab( index, 'target', event.target.value );
										},
									},
									targets.map( function ( target ) {
										return el( 'option', { value: target.value, key: target.value }, target.label );
									} )
								)
							),
							el( TextControl, {
								label: __( '自定义 URL', 'nerv-core' ),
								value: tab.url || '',
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									updateTab( index, 'url', value );
								},
							} )
						);
					} )
				),
				el(
					'div',
					{ className: 'nerv-control-fieldset nerv-control-fieldset--wide' },
					el( 'h4', null, __( 'MORE 页面模块', 'nerv-core' ) ),
					el(
						'div',
						{ className: 'nerv-control-more-section-grid' },
						[ 'status', 'monitor', 'alert', 'search', 'footer' ].map( function ( section ) {
							return el( CheckboxControl, {
								key: section,
								label: section.toUpperCase(),
								checked: !! form.moreSections[ section ],
								__nextHasNoMarginBottom: true,
								onChange: function ( value ) {
									updateSection( section, value );
								},
							} );
						} )
					),
					formData.moreUrl ? el( 'a', { className: 'nerv-control-inline-link', href: formData.moreUrl, target: '_blank', rel: 'noreferrer' }, __( '预览 MORE 页面', 'nerv-core' ) ) : null
				),
				el(
					'div',
					{ className: 'nerv-control-mobile-preview' },
					form.tabs.filter( function ( tab ) {
						return !! tab.enabled;
					} ).slice( 0, 5 ).map( function ( tab ) {
						return el(
							'span',
							{ key: tab.id || tab.label },
							el( 'b', null, tab.icon || 'grid' ),
							el( 'strong', null, tab.label || '' )
						);
					} )
				),
				el(
					'div',
					{ className: 'nerv-control-actions' },
					el(
						Button,
						{ variant: 'primary', isBusy: saving, disabled: saving, onClick: saveSettings },
						saving ? __( '保存中...', 'nerv-core' ) : __( '保存移动端设置', 'nerv-core' )
					)
				)
			)
		);
	}

	function PanelsPanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.panels ? data.forms.panels : {};
		const columns = formData.columns || [];
		const contentTypes = formData.contentTypes || [];
		const sourcePanels = formData.panels || [];
		const [ form, setForm ] = useState( clonePanelsForm( formData ) );
		const [ saving, setSaving ] = useState( false );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );
		const [ draggedPanel, setDraggedPanel ] = useState( null );
		const [ dragOverPanel, setDragOverPanel ] = useState( null );
		const [ draggedCustomPanel, setDraggedCustomPanel ] = useState( null );
		const [ dragOverCustomPanel, setDragOverCustomPanel ] = useState( null );

		function updatePanel( index, key, value ) {
			const panels = form.panels.map( function ( panel, panelIndex ) {
				return panelIndex === index ? Object.assign( {}, panel, { [ key ]: value } ) : panel;
			} );
			setForm( Object.assign( {}, form, { panels: panels } ) );
		}

		function movePanel( index, direction ) {
			const nextIndex = index + direction;
			if ( nextIndex < 0 || nextIndex >= form.panels.length ) {
				return;
			}

			const panels = form.panels.slice();
			const current = panels[ index ];
			panels[ index ] = panels[ nextIndex ];
			panels[ nextIndex ] = current;
			setForm( Object.assign( {}, form, { panels: normalizePanelOrder( panels ) } ) );
		}

		function dropPanel( index ) {
			if ( null === draggedPanel ) {
				return;
			}

			setForm( Object.assign( {}, form, { panels: normalizePanelOrder( reorderPanels( form.panels, draggedPanel, index ) ) } ) );
			setDraggedPanel( null );
			setDragOverPanel( null );
		}

		function updatePanelField( index, fieldKey, value ) {
			const panels = form.panels.map( function ( panel, panelIndex ) {
				if ( panelIndex !== index ) {
					return panel;
				}
				return Object.assign( {}, panel, {
					fields: Object.assign( {}, panel.fields, { [ fieldKey ]: value } ),
				} );
			} );
			setForm( Object.assign( {}, form, { panels: panels } ) );
		}

		function updatePanelRow( index, rowIndex, key, value ) {
			const panels = form.panels.map( function ( panel, panelIndex ) {
				if ( panelIndex !== index ) {
					return panel;
				}
				const rows = ( panel.rows || [] ).map( function ( row, currentRowIndex ) {
					if ( currentRowIndex !== rowIndex ) {
						return row;
					}
					const nextValue = 'level' === key ? Math.min( 100, Math.max( 0, parseInt( value, 10 ) || 0 ) ) : value;
					return Object.assign( {}, row, { [ key ]: nextValue } );
				} );
				return Object.assign( {}, panel, { rows: rows } );
			} );
			setForm( Object.assign( {}, form, { panels: panels } ) );
		}

		function addPanelRow( index ) {
			const panels = form.panels.map( function ( panel, panelIndex ) {
				if ( panelIndex !== index ) {
					return panel;
				}
				if ( ( panel.rows || [] ).length >= 12 ) {
					return panel;
				}
				let row = { label: '', value: '' };
				if ( 'monitor' === panel.rowType ) {
					row = { label: '', value: '0%', level: 0 };
				} else if ( 'status' === panel.rowType ) {
					row = { label: '', value: '', state: 'green' };
				}
				return Object.assign( {}, panel, { rows: ( panel.rows || [] ).concat( row ) } );
			} );
			setForm( Object.assign( {}, form, { panels: panels } ) );
		}

		function removePanelRow( index, rowIndex ) {
			const panels = form.panels.map( function ( panel, panelIndex ) {
				if ( panelIndex !== index ) {
					return panel;
				}
				const rows = ( panel.rows || [] ).filter( function ( row, currentRowIndex ) {
					return currentRowIndex !== rowIndex;
				} );
				return Object.assign( {}, panel, { rows: rows } );
			} );
			setForm( Object.assign( {}, form, { panels: panels } ) );
		}

		function updateCustomPanel( index, key, value ) {
			const customPanels = form.customPanels.map( function ( panel, panelIndex ) {
				return panelIndex === index ? Object.assign( {}, panel, { [ key ]: value } ) : panel;
			} );
			setForm( Object.assign( {}, form, { customPanels: customPanels } ) );
		}

		function addCustomPanel() {
			if ( form.customPanels.length >= 20 ) {
				return;
			}

			const nextIndex = form.customPanels.length + 1;
			const customPanels = form.customPanels.concat( {
				id: 'custom_' + Date.now().toString( 36 ),
				label: __( '自定义面板', 'nerv-core' ) + ' ' + nextIndex,
				title: __( '自定义面板', 'nerv-core' ) + ' ' + nextIndex,
				subtitle: '',
				content: '',
				contentType: 'richtext',
				column: 'center',
				order: 10 + form.customPanels.length,
				enabled: true,
			} );
			setForm( Object.assign( {}, form, { customPanels: normalizeCustomPanelOrder( customPanels ) } ) );
		}

		function removeCustomPanel( index ) {
			const customPanels = form.customPanels.filter( function ( panel, panelIndex ) {
				return panelIndex !== index;
			} );
			setForm( Object.assign( {}, form, { customPanels: normalizeCustomPanelOrder( customPanels ) } ) );
		}

		function moveCustomPanel( index, direction ) {
			const nextIndex = index + direction;
			if ( nextIndex < 0 || nextIndex >= form.customPanels.length ) {
				return;
			}

			setForm( Object.assign( {}, form, { customPanels: normalizeCustomPanelOrder( reorderPanels( form.customPanels, index, nextIndex ) ) } ) );
		}

		function dropCustomPanel( index ) {
			if ( null === draggedCustomPanel ) {
				return;
			}

			setForm( Object.assign( {}, form, { customPanels: normalizeCustomPanelOrder( reorderPanels( form.customPanels, draggedCustomPanel, index ) ) } ) );
			setDraggedCustomPanel( null );
			setDragOverCustomPanel( null );
		}

		function sourcePanel( panelId ) {
			return sourcePanels.find( function ( panel ) {
				return panel.id === panelId;
			} ) || { fields: [] };
		}

		function enabledCount() {
			const staticCount = form.panels.filter( function ( panel ) {
				return !! panel.enabled;
			} ).length;
			const customCount = form.customPanels.filter( function ( panel ) {
				return !! panel.enabled;
			} ).length;
			return staticCount + customCount;
		}

		function panelRowTitle( panel ) {
			if ( 'monitor' === panel.rowType ) {
				return __( '监控行', 'nerv-core' );
			}
			if ( 'log' === panel.rowType ) {
				return __( '日志行', 'nerv-core' );
			}
			return __( '状态行', 'nerv-core' );
		}

		function panelSourceLabel( panel ) {
			if ( 'status' === panel.id ) {
				return __( '状态来源', 'nerv-core' );
			}
			if ( 'monitor' === panel.id ) {
				return __( '监控来源', 'nerv-core' );
			}
			return __( '日志来源', 'nerv-core' );
		}

		function saveSettings() {
			setSaving( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.panelsPath : '/nerv-core/v1/control-panels',
				method: 'POST',
				data: Object.assign( {}, form, {
					panels: normalizePanelOrder( form.panels ),
					customPanels: normalizeCustomPanelOrder( form.customPanels ),
				} ),
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( clonePanelsForm( response.dashboard.forms.panels ) );
					}
					setNotice( response.message || __( '面板设置已保存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( '面板设置保存失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form nerv-control-panel--layout' },
				el(
					'div',
					{ className: 'nerv-control-panel__title' },
					el( 'h3', null, __( 'NERV主题 · 面板', 'nerv-core' ) ),
					el(
						'span',
						{ className: 'nerv-control-status-pill nerv-control-status-pill--green' },
						String( enabledCount() ) + ' ' + __( '已启用', 'nerv-core' )
					)
				),
				el( 'p', { className: 'nerv-control-form-note' }, __( '不用改代码即可控制终端面板：可见性、列、保存顺序、注册文案字段、实时行和自定义内容面板。', 'nerv-core' ) ),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'nerv-control-panel-editor' },
					form.panels.map( function ( panel, index ) {
						const original = sourcePanel( panel.id );
						return el(
							'details',
							{
								className: 'nerv-control-panel-row' + ( draggedPanel === index ? ' is-dragging' : '' ) + ( dragOverPanel === index ? ' is-drag-over' : '' ),
								key: panel.id,
								open: index < 3,
								draggable: true,
								onDragStart: function ( event ) {
									setDraggedPanel( index );
									event.dataTransfer.effectAllowed = 'move';
									event.dataTransfer.setData( 'text/plain', panel.id );
								},
								onDragOver: function ( event ) {
									event.preventDefault();
									if ( dragOverPanel !== index ) {
										setDragOverPanel( index );
									}
								},
								onDragLeave: function () {
									if ( dragOverPanel === index ) {
										setDragOverPanel( null );
									}
								},
								onDrop: function ( event ) {
									event.preventDefault();
									dropPanel( index );
								},
								onDragEnd: function () {
									setDraggedPanel( null );
									setDragOverPanel( null );
								},
							},
							el(
								'summary',
								null,
								el( 'span', { className: 'nerv-control-panel-drag-handle', title: __( '拖动排序', 'nerv-core' ) }, String( index + 1 ).padStart( 2, '0' ) ),
								el( 'strong', null, panel.label || panel.id ),
								el( 'em', null, panel.column || 'center' )
							),
							el(
								'div',
								{ className: 'nerv-control-panel-row__body' },
								el(
									'div',
									{ className: 'nerv-control-panel-row__meta' },
									el( CheckboxControl, {
										label: __( '启用面板', 'nerv-core' ),
										checked: !! panel.enabled,
										__nextHasNoMarginBottom: true,
										onChange: function ( value ) {
											updatePanel( index, 'enabled', value );
										},
									} ),
									el(
										'div',
										{ className: 'nerv-control-panel-order' },
										el( 'span', null, __( '排序', 'nerv-core' ) + ' ' + String( index + 1 ).padStart( 2, '0' ) ),
										el(
											Button,
											{ variant: 'secondary', disabled: 0 === index, onClick: function () { movePanel( index, -1 ); } },
											__( '上移', 'nerv-core' )
										),
										el(
											Button,
											{ variant: 'secondary', disabled: index === form.panels.length - 1, onClick: function () { movePanel( index, 1 ); } },
											__( '下移', 'nerv-core' )
										)
									),
									el(
										'label',
										{ className: 'nerv-control-select-field' },
										el( 'span', null, __( '列位置', 'nerv-core' ) ),
										el(
											'select',
											{
												value: panel.column || 'center',
												onChange: function ( event ) {
													updatePanel( index, 'column', event.target.value );
												},
											},
											columns.map( function ( column ) {
												return el( 'option', { value: column.value, key: column.value }, column.label );
											} )
										)
									),
									( panel.sourceOptions || [] ).length ? el(
										'label',
										{ className: 'nerv-control-select-field' },
										el( 'span', null, panelSourceLabel( panel ) ),
										el(
											'select',
											{
												value: panel.source || 'decorative',
												onChange: function ( event ) {
													updatePanel( index, 'source', event.target.value );
												},
											},
											( panel.sourceOptions || [] ).map( function ( option ) {
												return el( 'option', { value: option.value, key: option.value }, option.label );
											} )
										)
									) : null
								),
								el(
									'div',
									{ className: 'nerv-control-panel-fields' },
									( original.fields || [] ).map( function ( field ) {
										return el( TextControl, {
											key: field.key,
											label: field.label || field.key,
											value: panel.fields[ field.key ] || '',
											help: field.default && field.default !== panel.fields[ field.key ] ? __( '已不同于默认值。', 'nerv-core' ) : '',
											__next40pxDefaultSize: true,
											onChange: function ( value ) {
												updatePanelField( index, field.key, value );
											},
										} );
									} )
								),
								panel.rowType ? el(
									'div',
									{ className: 'nerv-control-panel-repeater' },
									el(
										'div',
										{ className: 'nerv-control-panel-repeater__head' },
										el( 'strong', null, panelRowTitle( panel ) ),
										el(
											Button,
											{ variant: 'secondary', disabled: ( panel.rows || [] ).length >= 12, onClick: function () { addPanelRow( index ); } },
											__( '添加行', 'nerv-core' )
										)
									),
									( panel.rows || [] ).map( function ( row, rowIndex ) {
										return el(
											'div',
											{ className: 'nerv-control-panel-repeater__row nerv-control-panel-repeater__row--' + panel.rowType, key: panel.id + '-row-' + rowIndex },
											el( TextControl, {
												label: __( '标签', 'nerv-core' ),
												value: row.label || '',
												__next40pxDefaultSize: true,
												onChange: function ( value ) {
													updatePanelRow( index, rowIndex, 'label', value );
												},
											} ),
											el( TextControl, {
												label: __( '值', 'nerv-core' ),
												value: row.value || '',
												__next40pxDefaultSize: true,
												onChange: function ( value ) {
													updatePanelRow( index, rowIndex, 'value', value );
												},
											} ),
											'monitor' === panel.rowType ? el( TextControl, {
												label: __( '等级', 'nerv-core' ),
												type: 'number',
												min: 0,
												max: 100,
												value: String( 'undefined' === typeof row.level ? 0 : row.level ),
												__next40pxDefaultSize: true,
												onChange: function ( value ) {
													updatePanelRow( index, rowIndex, 'level', value );
												},
											} ) : null,
											'status' === panel.rowType ? el(
												'label',
												{ className: 'nerv-control-select-field' },
												el( 'span', null, __( '状态', 'nerv-core' ) ),
												el(
													'select',
													{
														value: row.state || 'green',
														onChange: function ( event ) {
															updatePanelRow( index, rowIndex, 'state', event.target.value );
														},
													},
													( panel.stateOptions || [] ).map( function ( option ) {
														return el( 'option', { value: option.value, key: option.value }, option.label );
													} )
												)
											) : null,
											el(
												Button,
												{ variant: 'secondary', isDestructive: true, onClick: function () { removePanelRow( index, rowIndex ); } },
												__( '删除', 'nerv-core' )
											)
										);
									} )
								) : null
							)
						);
					} )
				),
				el(
					'div',
					{ className: 'nerv-control-custom-panels' },
					el(
						'div',
						{ className: 'nerv-control-custom-panels__head' },
						el( 'strong', null, __( '自定义面板', 'nerv-core' ) ),
						el(
							Button,
							{ variant: 'secondary', disabled: form.customPanels.length >= 20, onClick: addCustomPanel },
							__( '添加自定义面板', 'nerv-core' )
						)
					),
					form.customPanels.length ? form.customPanels.map( function ( panel, index ) {
						return el(
							'details',
							{
								className: 'nerv-control-panel-row nerv-control-panel-row--custom' + ( draggedCustomPanel === index ? ' is-dragging' : '' ) + ( dragOverCustomPanel === index ? ' is-drag-over' : '' ),
								key: panel.id,
								open: index < 2,
								draggable: true,
								onDragStart: function ( event ) {
									setDraggedCustomPanel( index );
									event.dataTransfer.effectAllowed = 'move';
									event.dataTransfer.setData( 'text/plain', panel.id );
								},
								onDragOver: function ( event ) {
									event.preventDefault();
									if ( dragOverCustomPanel !== index ) {
										setDragOverCustomPanel( index );
									}
								},
								onDragLeave: function () {
									if ( dragOverCustomPanel === index ) {
										setDragOverCustomPanel( null );
									}
								},
								onDrop: function ( event ) {
									event.preventDefault();
									dropCustomPanel( index );
								},
								onDragEnd: function () {
									setDraggedCustomPanel( null );
									setDragOverCustomPanel( null );
								},
							},
							el(
								'summary',
								null,
								el( 'span', { className: 'nerv-control-panel-drag-handle', title: __( '拖动排序', 'nerv-core' ) }, 'C' + String( index + 1 ).padStart( 2, '0' ) ),
								el( 'strong', null, panel.title || panel.id ),
								el( 'em', null, panel.column || 'center' )
							),
							el(
								'div',
								{ className: 'nerv-control-panel-row__body' },
								el(
									'div',
									{ className: 'nerv-control-panel-row__meta nerv-control-panel-row__meta--custom' },
									el( CheckboxControl, {
										label: __( '启用面板', 'nerv-core' ),
										checked: !! panel.enabled,
										__nextHasNoMarginBottom: true,
										onChange: function ( value ) {
											updateCustomPanel( index, 'enabled', value );
										},
									} ),
									el(
										'label',
										{ className: 'nerv-control-select-field' },
										el( 'span', null, __( '列位置', 'nerv-core' ) ),
										el(
											'select',
											{
												value: panel.column || 'center',
												onChange: function ( event ) {
													updateCustomPanel( index, 'column', event.target.value );
												},
											},
											columns.map( function ( column ) {
												return el( 'option', { value: column.value, key: column.value }, column.label );
											} )
										)
									),
									el(
										'label',
										{ className: 'nerv-control-select-field' },
										el( 'span', null, __( '内容类型', 'nerv-core' ) ),
										el(
											'select',
											{
												value: panel.contentType || 'richtext',
												onChange: function ( event ) {
													updateCustomPanel( index, 'contentType', event.target.value );
												},
											},
											contentTypes.map( function ( option ) {
												return el( 'option', { value: option.value, key: option.value }, option.label );
											} )
										)
									),
									el(
										'div',
										{ className: 'nerv-control-panel-order' },
										el( 'span', null, __( '排序', 'nerv-core' ) + ' C' + String( index + 1 ).padStart( 2, '0' ) ),
										el(
											Button,
											{ variant: 'secondary', disabled: 0 === index, onClick: function () { moveCustomPanel( index, -1 ); } },
											__( '上移', 'nerv-core' )
										),
										el(
											Button,
											{ variant: 'secondary', disabled: index === form.customPanels.length - 1, onClick: function () { moveCustomPanel( index, 1 ); } },
											__( '下移', 'nerv-core' )
										)
									),
									el(
										Button,
										{ variant: 'secondary', isDestructive: true, onClick: function () { removeCustomPanel( index ); } },
										__( '删除', 'nerv-core' )
									)
								),
								el(
									'div',
									{ className: 'nerv-control-custom-panel-fields' },
									el( TextControl, {
										label: __( '面板标题', 'nerv-core' ),
										value: panel.title || '',
										__next40pxDefaultSize: true,
										onChange: function ( value ) {
											updateCustomPanel( index, 'title', value );
										},
									} ),
									el( TextControl, {
										label: __( '副标题', 'nerv-core' ),
										value: panel.subtitle || '',
										__next40pxDefaultSize: true,
										onChange: function ( value ) {
											updateCustomPanel( index, 'subtitle', value );
										},
									} ),
									el( TextareaControl, {
										label: __( '内容', 'nerv-core' ),
										value: panel.content || '',
										rows: 6,
										onChange: function ( value ) {
											updateCustomPanel( index, 'content', value );
										},
									} )
								)
							)
						);
					} ) : el( 'p', { className: 'nerv-control-form-note' }, __( '还没有自定义面板。', 'nerv-core' ) )
				),
				el(
					'div',
					{ className: 'nerv-control-panel-preview' },
					form.panels.concat( form.customPanels ).map( function ( panel ) {
						return el(
							'span',
							{ className: panel.enabled ? 'is-on' : 'is-off', key: panel.id },
							panel.label || panel.title || panel.id
						);
					} )
				),
				el(
					'div',
					{ className: 'nerv-control-actions' },
					formData.previewUrl ? el( 'a', { className: 'nerv-control-inline-link', href: formData.previewUrl, target: '_blank', rel: 'noreferrer' }, __( '预览首页', 'nerv-core' ) ) : null,
					el(
						Button,
						{ variant: 'primary', isBusy: saving, disabled: saving, onClick: saveSettings },
						saving ? __( '保存中...', 'nerv-core' ) : __( '保存面板设置', 'nerv-core' )
					)
				)
			)
		);
	}

	function EffectsPanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.effects ? data.forms.effects : {};
		const [ form, setForm ] = useState( cloneEffectsForm( formData ) );
		const [ saving, setSaving ] = useState( false );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );
		const [ effectPresetText, setEffectPresetText ] = useState( '' );

		function updateField( key, value ) {
			setForm( Object.assign( {}, form, { [ key ]: value } ) );
		}

		function updateNestedField( group, key, value ) {
			setForm( Object.assign( {}, form, { [ group ]: Object.assign( {}, form[ group ] || {}, { [ key ]: value } ) } ) );
		}

		function applyPreset( presetKey ) {
			const preset = ( form.presets || [] ).find( function ( item ) {
				return item.value === presetKey;
			} );
			if ( ! preset || ! preset.data ) {
				updateField( 'preset', presetKey );
				return;
			}
			setForm( Object.assign( {}, form, preset.data, {
				preset: presetKey,
				presets: form.presets,
				desktop: Object.assign( {}, form.desktop || {}, { enabled: preset.data.enabled !== false, intensity: preset.data.intensity || form.intensity || 65 } ),
				mobile: Object.assign( {}, form.mobile || {}, preset.data.mobile || {} ),
			} ) );
		}

		function saveSettings() {
			setSaving( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.effectsPath : '/nerv-core/v1/control-effects',
				method: 'POST',
				data: form,
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( cloneEffectsForm( response.dashboard.forms.effects ) );
					}
					setNotice( response.message || __( '特效设置已保存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( '特效设置保存失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		function exportEffectsPreset() {
			setEffectPresetText( JSON.stringify( effectsPresetDocument( form ), null, 2 ) );
			setNotice( __( '特效预设 JSON 已导出。', 'nerv-core' ) );
			setError( '' );
		}

		function importEffectsPreset() {
			setNotice( '' );
			setError( '' );
			try {
				setForm( mergeEffectsPresetDocument( form, effectPresetText ) );
				setNotice( __( '特效预设 JSON 已导入，保存后应用到前台。', 'nerv-core' ) );
			} catch ( presetError ) {
				setError( presetError && presetError.message ? presetError.message : __( '特效预设 JSON 导入失败。', 'nerv-core' ) );
			}
		}

		const effectToggles = [
			{ key: 'backgroundGrid', label: __( '背景网格', 'nerv-core' ) },
			{ key: 'scanlines', label: __( 'CRT 扫描线', 'nerv-core' ) },
			{ key: 'panelGlow', label: __( '面板辉光', 'nerv-core' ) },
			{ key: 'motion', label: __( '动效过渡', 'nerv-core' ) },
		];

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form nerv-control-panel--effects' },
				el(
					'div',
					{ className: 'nerv-control-panel__title' },
					el( 'h3', null, __( 'NERV主题 · 特效', 'nerv-core' ) ),
					el(
						'span',
						{ className: 'nerv-control-status-pill ' + ( form.enabled ? 'nerv-control-status-pill--green' : 'nerv-control-status-pill--amber' ) },
						form.enabled ? __( '已启用', 'nerv-core' ) : __( '已停用', 'nerv-core' )
					)
				),
				el( 'p', { className: 'nerv-control-form-note' }, __( '调整前台终端外壳的展示特效；偏好减少动效的访客仍会使用浏览器级降级。', 'nerv-core' ) ),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'nerv-control-effects-grid' },
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '总开关', 'nerv-core' ) ),
						el(
							'label',
							{ className: 'nerv-control-select-field' },
							el( 'span', null, __( '预设', 'nerv-core' ) ),
							el(
								'select',
								{
									value: form.preset,
									onChange: function ( event ) {
										applyPreset( event.target.value );
									},
								},
								( form.presets || [] ).map( function ( preset ) {
									return el( 'option', { value: preset.value, key: preset.value }, preset.label );
								} )
							)
						),
						el( CheckboxControl, {
							label: __( '启用终端特效', 'nerv-core' ),
							checked: !! form.enabled,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								updateField( 'enabled', value );
							},
						} ),
						el(
							'label',
							{ className: 'nerv-control-range-field' },
							el( 'span', null, __( '强度', 'nerv-core' ) + ': ' + String( form.intensity ) + '%' ),
							el( 'input', {
								type: 'range',
								min: '0',
								max: '100',
								step: '5',
								value: form.intensity,
								disabled: ! form.enabled,
								onChange: function ( event ) {
									updateField( 'intensity', parseInt( event.target.value, 10 ) || 0 );
								},
							} )
						)
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '特效通道', 'nerv-core' ) ),
						el(
							'div',
							{ className: 'nerv-control-effect-toggles' },
							effectToggles.map( function ( toggle ) {
								return el( CheckboxControl, {
									key: toggle.key,
									label: toggle.label,
									checked: !! form[ toggle.key ],
									disabled: ! form.enabled,
									__nextHasNoMarginBottom: true,
									onChange: function ( value ) {
										updateField( toggle.key, value );
									},
								} );
							} )
						)
					)
				),
				el(
					'div',
					{ className: 'nerv-control-effects-device-grid' },
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '桌面端覆盖', 'nerv-core' ) ),
						el( CheckboxControl, {
							label: __( '桌面端启用特效', 'nerv-core' ),
							checked: !! form.desktop.enabled,
							disabled: ! form.enabled,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								updateNestedField( 'desktop', 'enabled', value );
							},
						} ),
						el(
							'label',
							{ className: 'nerv-control-range-field' },
							el( 'span', null, __( '桌面端强度', 'nerv-core' ) + ': ' + String( form.desktop.intensity ) + '%' ),
							el( 'input', {
								type: 'range',
								min: '0',
								max: '100',
								step: '5',
								value: form.desktop.intensity,
								disabled: ! form.enabled || ! form.desktop.enabled,
								onChange: function ( event ) {
									updateNestedField( 'desktop', 'intensity', parseInt( event.target.value, 10 ) || 0 );
								},
							} )
						)
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '移动端覆盖', 'nerv-core' ) ),
						el( CheckboxControl, {
							label: __( '移动端启用特效', 'nerv-core' ),
							checked: !! form.mobile.enabled,
							disabled: ! form.enabled,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								updateNestedField( 'mobile', 'enabled', value );
							},
						} ),
						el(
							'div',
							{ className: 'nerv-control-effect-toggles' },
							effectToggles.map( function ( toggle ) {
								return el( CheckboxControl, {
									key: 'mobile-' + toggle.key,
									label: __( '移动端', 'nerv-core' ) + ' · ' + toggle.label,
									checked: !! form.mobile[ toggle.key ],
									disabled: ! form.enabled || ! form.mobile.enabled,
									__nextHasNoMarginBottom: true,
									onChange: function ( value ) {
										updateNestedField( 'mobile', toggle.key, value );
									},
								} );
							} )
						),
						el(
							'label',
							{ className: 'nerv-control-range-field' },
							el( 'span', null, __( '移动端强度', 'nerv-core' ) + ': ' + String( form.mobile.intensity ) + '%' ),
							el( 'input', {
								type: 'range',
								min: '0',
								max: '100',
								step: '5',
								value: form.mobile.intensity,
								disabled: ! form.enabled || ! form.mobile.enabled,
								onChange: function ( event ) {
									updateNestedField( 'mobile', 'intensity', parseInt( event.target.value, 10 ) || 0 );
								},
							} )
						)
					)
				),
				el(
					'div',
					{ className: 'nerv-control-effects-preview', style: { '--preview-intensity': String( form.desktop.intensity / 100 ), '--preview-intensity-mobile': String( form.mobile.intensity / 100 ) } },
					el( 'span', { className: form.enabled && form.backgroundGrid ? 'is-on' : 'is-off' }, __( 'GRID', 'nerv-core' ) ),
					el( 'span', { className: form.enabled && form.scanlines ? 'is-on' : 'is-off' }, __( 'SCANLINES', 'nerv-core' ) ),
					el( 'span', { className: form.enabled && form.panelGlow ? 'is-on' : 'is-off' }, __( 'GLOW', 'nerv-core' ) ),
					el( 'span', { className: form.enabled && form.motion ? 'is-on' : 'is-off' }, __( 'MOTION', 'nerv-core' ) ),
					el( 'span', { className: form.enabled && form.mobile.enabled ? 'is-on' : 'is-off' }, __( 'MOBILE', 'nerv-core' ) + ' ' + String( form.mobile.intensity ) + '%' )
				),
				el(
					'div',
					{ className: 'nerv-control-effect-preset-box' },
					el( 'h4', null, __( '特效预设 JSON', 'nerv-core' ) ),
					el( 'p', null, __( '只导出/导入特效设置。导入 JSON 会先更新表单，确认预览后再保存。', 'nerv-core' ) ),
					el( TextareaControl, {
						label: __( '特效预设 JSON', 'nerv-core' ),
						value: effectPresetText,
						rows: 7,
						__nextHasNoMarginBottom: true,
						onChange: function ( value ) {
							setEffectPresetText( value );
						},
					} ),
					el(
						'div',
						{ className: 'nerv-control-preset-actions' },
						el(
							Button,
							{ variant: 'secondary', disabled: saving, onClick: exportEffectsPreset },
							__( '导出特效 JSON', 'nerv-core' )
						),
						el(
							Button,
							{ variant: 'primary', disabled: saving || ! effectPresetText.trim(), onClick: importEffectsPreset },
							__( '导入特效 JSON', 'nerv-core' )
						)
					)
				),
				el(
					'div',
					{ className: 'nerv-control-actions' },
					formData.previewUrl ? el( 'a', { className: 'nerv-control-inline-link', href: formData.previewUrl, target: '_blank', rel: 'noreferrer' }, __( '预览首页', 'nerv-core' ) ) : null,
					el(
						Button,
						{ variant: 'primary', isBusy: saving, disabled: saving, onClick: saveSettings },
						saving ? __( '保存中...', 'nerv-core' ) : __( '保存特效设置', 'nerv-core' )
					)
				)
			)
		);
	}

	function AppearancePanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.appearance ? data.forms.appearance : {};
		const [ form, setForm ] = useState( cloneAppearanceForm( formData ) );
		const [ saving, setSaving ] = useState( false );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );

		function setField( key, value ) {
			setForm( Object.assign( {}, form, { [ key ]: value } ) );
		}

		function saveSettings() {
			setSaving( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.appearancePath : '/nerv-core/v1/control-appearance',
				method: 'POST',
				data: {
					palette: form.palette,
					mode: form.mode,
				},
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( cloneAppearanceForm( response.dashboard.forms.appearance ) );
					}
					setNotice( response.message || __( '配色设置已保存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( '配色设置保存失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form nerv-control-panel--appearance' },
				el(
					'div',
					{ className: 'nerv-control-panel__title' },
					el( 'h3', null, __( 'NERV主题 · 配色', 'nerv-core' ) ),
					el( 'span', { className: 'nerv-control-status-pill nerv-control-status-pill--green' }, form.mode + ' / ' + form.palette )
				),
				el( 'p', { className: 'nerv-control-form-note' }, __( '选择前台配色和白天/夜间模式；区块编辑器保持白底黑字，方便写作。', 'nerv-core' ) ),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'nerv-control-appearance-grid' },
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '配色方案', 'nerv-core' ) ),
						el(
							'div',
							{ className: 'nerv-control-palette-grid' },
							( form.palettes || [] ).map( function ( palette ) {
								return el(
									'button',
									{
										type: 'button',
										key: palette.value,
										className: 'nerv-control-palette-swatch nerv-control-palette-swatch--' + palette.value + ( form.palette === palette.value ? ' is-selected' : '' ),
										onClick: function () {
											setField( 'palette', palette.value );
										},
									},
									el( 'span', null, palette.label ),
									el( 'i', null )
								);
							} )
						)
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '白天 / 夜间模式', 'nerv-core' ) ),
						el(
							'div',
							{ className: 'nerv-control-mode-grid' },
							( form.modes || [] ).map( function ( mode ) {
								return el(
									'button',
									{
										type: 'button',
										key: mode.value,
										className: 'nerv-control-mode-card nerv-control-mode-card--' + mode.value + ( form.mode === mode.value ? ' is-selected' : '' ),
										onClick: function () {
											setField( 'mode', mode.value );
										},
									},
									el( 'strong', null, mode.label ),
									el( 'span', null, mode.value )
								);
							} )
						),
						el(
							'div',
							{ className: 'nerv-control-appearance-preview', 'data-palette': form.palette, 'data-mode': form.mode },
							el( 'span', null, __( '预览令牌', 'nerv-core' ) ),
							el( 'strong', null, form.palette.toUpperCase() ),
							el( 'small', null, form.mode.toUpperCase() )
						)
					)
				),
				el(
					'div',
					{ className: 'nerv-control-actions' },
					form.previewUrl ? el( 'a', { className: 'nerv-control-inline-link', href: form.previewUrl, target: '_blank', rel: 'noreferrer' }, __( '预览首页', 'nerv-core' ) ) : null,
					el(
						Button,
						{ variant: 'primary', isBusy: saving, disabled: saving, onClick: saveSettings },
						saving ? __( '保存中...', 'nerv-core' ) : __( '保存配色设置', 'nerv-core' )
					)
				)
			)
		);
	}

	function SocialPanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.social ? data.forms.social : {};
		const platforms = formData.platforms || [];
		const [ form, setForm ] = useState( cloneSocialForm( formData ) );
		const [ saving, setSaving ] = useState( false );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );

		function setField( key, value ) {
			setForm( Object.assign( {}, form, { [ key ]: value } ) );
		}

		function updateLink( index, key, value ) {
			const links = form.links.map( function ( link, linkIndex ) {
				return linkIndex === index ? Object.assign( {}, link, { [ key ]: value } ) : link;
			} );
			setField( 'links', links );
		}

		function addLink() {
			setField(
				'links',
				form.links.concat( [ { key: 'website', label: 'WEB', url: '', qrUrl: '', enabled: true, rel: 'me noopener noreferrer' } ] )
			);
		}

		function removeLink( index ) {
			setField( 'links', form.links.filter( function ( link, linkIndex ) {
				return linkIndex !== index;
			} ) );
		}

		function saveSettings() {
			setSaving( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.socialPath : '/nerv-core/v1/control-social',
				method: 'POST',
				data: form,
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( cloneSocialForm( response.dashboard.forms.social ) );
					}
					setNotice( response.message || __( '社交设置已保存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( '社交设置保存失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form nerv-control-panel--social' },
				el(
					'div',
					{ className: 'nerv-control-panel__title' },
					el( 'h3', null, __( 'NERV主题 · 社交', 'nerv-core' ) ),
					el(
						'span',
						{ className: 'nerv-control-status-pill nerv-control-status-pill--' + ( form.enabled ? 'green' : 'red' ) },
						form.enabled ? __( '社交档案已启用', 'nerv-core' ) : __( '社交档案已停用', 'nerv-core' )
					)
				),
				el( 'p', { className: 'nerv-control-form-note' }, __( '设置 Pilot Profile 面板和机器可读身份信号使用的全局社交链接；作者卡片仍可从用户资料单独覆盖。', 'nerv-core' ) ),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'nerv-control-switches' },
					el( CheckboxControl, {
						label: __( '在 Pilot Profile 中显示全局社交链接', 'nerv-core' ),
						checked: form.enabled,
						__nextHasNoMarginBottom: true,
						onChange: function ( value ) {
							setField( 'enabled', value );
						},
					} ),
					el( CheckboxControl, {
						label: __( '外部社交链接在新窗口打开', 'nerv-core' ),
						checked: form.openNewTab,
						__nextHasNoMarginBottom: true,
						onChange: function ( value ) {
							setField( 'openNewTab', value );
						},
					} )
				),
				el(
					'div',
					{ className: 'nerv-control-social-editor' },
					form.links.map( function ( link, index ) {
						return el(
							'div',
							{ className: 'nerv-control-social-row', key: String( index ) + '-' + ( link.key || 'social' ) },
							el( 'strong', null, String( index + 1 ).padStart( 2, '0' ) ),
							el( CheckboxControl, {
								label: __( '已启用', 'nerv-core' ),
								checked: !! link.enabled,
								__nextHasNoMarginBottom: true,
								onChange: function ( value ) {
									updateLink( index, 'enabled', value );
								},
							} ),
							el(
								'label',
								{ className: 'nerv-control-select-field' },
								el( 'span', null, __( '平台', 'nerv-core' ) ),
								el(
									'select',
									{
										value: link.key || 'website',
										onChange: function ( event ) {
											updateLink( index, 'key', event.target.value );
										},
									},
									platforms.map( function ( platform ) {
										return el( 'option', { value: platform.value, key: platform.value }, platform.label );
									} )
								)
							),
							el( TextControl, {
								label: __( '标签', 'nerv-core' ),
								value: link.label || '',
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									updateLink( index, 'label', value );
								},
							} ),
							el( TextControl, {
								label: __( 'URL', 'nerv-core' ),
								type: 'url',
								value: link.url || '',
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									updateLink( index, 'url', value );
								},
							} ),
							el( TextControl, {
								label: __( '二维码图片 URL', 'nerv-core' ),
								type: 'url',
								value: link.qrUrl || '',
								help: __( '用于微信二维码弹层；普通社交链接可留空。', 'nerv-core' ),
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									updateLink( index, 'qrUrl', value );
								},
							} ),
							el( TextControl, {
								label: __( 'Rel', 'nerv-core' ),
								value: link.rel || '',
								__next40pxDefaultSize: true,
								onChange: function ( value ) {
									updateLink( index, 'rel', value );
								},
							} ),
							el(
								Button,
								{ variant: 'secondary', isDestructive: true, onClick: function () { removeLink( index ); } },
								__( '删除', 'nerv-core' )
							)
						);
					} )
				),
				el(
					'div',
					{ className: 'nerv-control-social-tools' },
					el( Button, { variant: 'secondary', onClick: addLink }, __( '添加社交链接', 'nerv-core' ) ),
					el( 'a', { className: 'nerv-control-inline-link', href: formData.previewUrl || '#', target: '_blank', rel: 'noreferrer' }, __( '预览 Pilot Profile', 'nerv-core' ) ),
					el( 'span', null, String( formData.sameAsCount || 0 ) + ' sameAs' )
				),
				el(
					'div',
					{ className: 'nerv-control-social-preview' },
					form.links.filter( function ( link ) {
						return !! link.enabled && !! link.url;
					} ).map( function ( link, index ) {
						return el( 'span', { key: String( index ) + '-' + ( link.url || '' ) }, link.label || link.key || 'WEB' );
					} )
				),
				el(
					'div',
					{ className: 'nerv-control-actions' },
					el(
						Button,
						{ variant: 'primary', isBusy: saving, disabled: saving, onClick: saveSettings },
						saving ? __( '保存中...', 'nerv-core' ) : __( '保存社交设置', 'nerv-core' )
					)
				)
			)
		);
	}

	function PartnersPanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.partners ? data.forms.partners : {};
		const [ form, setForm ] = useState( clonePartnersForm( formData ) );
		const [ saving, setSaving ] = useState( false );
		const [ testing, setTesting ] = useState( false );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );
		const rows = formData.rows || [];
		const summary = formData.health && formData.health.summary ? formData.health.summary : {};

		function updateDisplay( key, value ) {
			setForm( Object.assign( {}, form, { display: Object.assign( {}, form.display, { [ key ]: value } ) } ) );
		}

		function updateHealth( key, value ) {
			setForm( Object.assign( {}, form, { health: Object.assign( {}, form.health, { [ key ]: value } ) } ) );
		}

		function saveSettings() {
			setSaving( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.partnersPath : '/nerv-core/v1/control-partners',
				method: 'POST',
				data: form,
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( clonePartnersForm( response.dashboard.forms.partners ) );
					}
					setNotice( response.message || __( '合作伙伴设置已保存。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( '合作伙伴设置保存失败。', 'nerv-core' ) );
				} )
				.finally( function () {
					setSaving( false );
				} );
		}

		function runHealthTest() {
			setTesting( true );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.partnerTestPath : '/nerv-core/v1/control-partner-health-test',
				method: 'POST',
				data: {},
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						setForm( clonePartnersForm( response.dashboard.forms.partners ) );
					}
					setNotice( response.message || __( '合作伙伴健康测试已完成。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( '合作伙伴健康测试未能完成。', 'nerv-core' ) );
				} )
				.finally( function () {
					setTesting( false );
				} );
		}

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form nerv-control-panel--partners' },
				el(
					'div',
					{ className: 'nerv-control-panel__title' },
					el( 'h3', null, __( 'NERV主题 · 合作伙伴', 'nerv-core' ) ),
					el(
						'span',
						{ className: 'nerv-control-status-pill nerv-control-status-pill--green' },
						String( summary.online || 0 ) + '/' + String( summary.total || 0 ) + ' ONLINE'
					)
				),
				el( 'p', { className: 'nerv-control-form-note' }, __( '设置合作伙伴链接展示、申请文案、llms.txt 收录和健康探测阈值。', 'nerv-core' ) ),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'nerv-control-form-grid' },
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '合作伙伴展示', 'nerv-core' ) ),
						el( CheckboxControl, {
							label: __( '在终端页脚显示推荐伙伴', 'nerv-core' ),
							checked: form.display.footerEnabled,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								updateDisplay( 'footerEnabled', value );
							},
						} ),
						el( TextControl, {
							label: __( '页脚数量', 'nerv-core' ),
							type: 'number',
							min: 1,
							max: 12,
							value: String( form.display.footerLimit ),
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								updateDisplay( 'footerLimit', value );
							},
						} ),
						el( CheckboxControl, {
							label: __( '在 llms.txt 收录合作伙伴', 'nerv-core' ),
							checked: form.display.llmsInclude,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								updateDisplay( 'llmsInclude', value );
							},
						} )
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset' },
						el( 'h4', null, __( '合作伙伴健康', 'nerv-core' ) ),
						el( CheckboxControl, {
							label: __( '启用定时合作伙伴健康检查', 'nerv-core' ),
							checked: form.health.enabled,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								updateHealth( 'enabled', value );
							},
						} ),
						el( TextControl, {
							label: __( '超时秒数', 'nerv-core' ),
							type: 'number',
							min: 1,
							max: 20,
							value: String( form.health.timeout ),
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								updateHealth( 'timeout', value );
							},
						} ),
						el( TextControl, {
							label: __( '慢速阈值秒数', 'nerv-core' ),
							type: 'number',
							min: 0.5,
							max: 10,
							step: 0.5,
							value: String( form.health.slowSeconds ),
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								updateHealth( 'slowSeconds', value );
							},
						} ),
						el( 'p', { className: 'nerv-control-mini' }, 'ONLINE ' + String( summary.online || 0 ) + ' / SLOW ' + String( summary.slow || 0 ) + ' / OFFLINE ' + String( summary.offline || 0 ) )
					),
					el(
						'div',
						{ className: 'nerv-control-fieldset nerv-control-fieldset--wide' },
						el( 'h4', null, __( '申请区块', 'nerv-core' ) ),
						el( CheckboxControl, {
							label: __( '在合作伙伴页面显示申请区块', 'nerv-core' ),
							checked: form.display.applicationEnabled,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								updateDisplay( 'applicationEnabled', value );
							},
						} ),
						el( TextControl, {
							label: __( '申请邮箱', 'nerv-core' ),
							type: 'email',
							value: form.display.applicationEmail,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								updateDisplay( 'applicationEmail', value );
							},
						} ),
						el( TextareaControl, {
							label: __( '申请文案', 'nerv-core' ),
							value: form.display.applicationText,
							rows: 3,
							__nextHasNoMarginBottom: true,
							onChange: function ( value ) {
								updateDisplay( 'applicationText', value );
							},
						} )
					)
				),
				el(
					'div',
					{ className: 'nerv-control-partner-actions' },
					el( 'a', { href: formData.links ? formData.links.list : '#', target: '_blank', rel: 'noreferrer' }, __( '管理合作伙伴', 'nerv-core' ) ),
					el( 'a', { href: formData.links ? formData.links.new : '#', target: '_blank', rel: 'noreferrer' }, __( '新增合作伙伴', 'nerv-core' ) ),
					el( 'a', { href: formData.links ? formData.links.archive : '#', target: '_blank', rel: 'noreferrer' }, __( '查看归档', 'nerv-core' ) )
				),
				el(
					'div',
					{ className: 'nerv-control-command-row' },
					el(
						'div',
						null,
						el( 'strong', null, __( '合作伙伴健康测试', 'nerv-core' ) ),
						el( 'span', null, __( '手动探测已发布的合作伙伴链接并刷新状态表。', 'nerv-core' ) )
					),
					el(
						Button,
						{ variant: 'secondary', isBusy: testing, disabled: testing || saving, onClick: runHealthTest },
						__( '运行合作伙伴健康测试', 'nerv-core' )
					)
				),
				el(
					'div',
					{ className: 'nerv-control-partner-table' },
					rows.length
						? rows.map( function ( row ) {
								return el(
									'div',
									{ className: 'nerv-control-partner-row nerv-control-partner-row--' + row.status, key: row.id },
									el( 'strong', null, row.title ),
									el( 'span', null, row.label ),
									el( 'em', null, row.featured ? __( '推荐', 'nerv-core' ) : __( '普通', 'nerv-core' ) ),
									el( 'small', null, ( row.message || row.url || '' ) + ( row.redirects ? ' · ' + String( row.redirects ) + ' redirects' : '' ) ),
									row.finalUrl && row.finalUrl !== row.url ? el( 'small', null, __( '最终 URL', 'nerv-core' ) + ': ' + row.finalUrl ) : null
								);
						  } )
						: el( 'p', { className: 'nerv-control-empty' }, __( '暂无合作伙伴。', 'nerv-core' ) )
				),
				el(
					'div',
					{ className: 'nerv-control-actions' },
					el(
						Button,
						{ variant: 'primary', isBusy: saving, disabled: saving, onClick: saveSettings },
						saving ? __( '保存中...', 'nerv-core' ) : __( '保存合作伙伴设置', 'nerv-core' )
					)
				)
			)
		);
	}

	function ToolsPanel( props ) {
		const data = props.data;
		const formData = data.forms && data.forms.tools ? data.forms.tools : {};
		const [ form, setForm ] = useState( cloneToolsForm( formData ) );
		const [ running, setRunning ] = useState( '' );
		const [ notice, setNotice ] = useState( '' );
		const [ error, setError ] = useState( '' );
		const [ presetText, setPresetText ] = useState( '' );
		const [ themeCheck, setThemeCheck ] = useState( form.themeCheck );
		const [ demoResult, setDemoResult ] = useState( form.demo );

		function runAction( action, extraData ) {
			setRunning( action );
			setNotice( '' );
			setError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.toolsActionPath : '/nerv-core/v1/control-tools-action',
				method: 'POST',
				data: Object.assign( { toolAction: action }, extraData || {} ),
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
						const nextForm = cloneToolsForm( response.dashboard.forms.tools );
						setForm( nextForm );
						if ( 'import_demo' !== action ) {
							setDemoResult( nextForm.demo );
						}
					}
					if ( response.result && response.result.preset ) {
						setPresetText( JSON.stringify( response.result.preset, null, 2 ) );
					}
					if ( 'run_theme_check' === action && response.result ) {
						setThemeCheck( Object.assign( {}, form.themeCheck, response.result ) );
					}
					if ( 'import_demo' === action && response.result ) {
						setDemoResult( Object.assign( {}, form.demo, response.result ) );
					}
					setNotice( response.message || __( '工具操作已完成。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setError( response && response.message ? response.message : __( '工具操作未能完成。', 'nerv-core' ) );
				} )
				.finally( function () {
					setRunning( '' );
				} );
		}

		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel nerv-control-panel--form nerv-control-panel--tools' },
				el(
					'div',
					{ className: 'nerv-control-panel__title' },
					el( 'h3', null, __( 'NERV主题 · 工具', 'nerv-core' ) ),
					el(
						'span',
						{ className: 'nerv-control-status-pill nerv-control-status-pill--green' },
						__( '工具就绪', 'nerv-core' )
					)
				),
				el( 'p', { className: 'nerv-control-form-note' }, __( '运行安全的 WordPress 维护操作，并检查本地构建/演示命令；打包仍保留在本地 CLI，不放进浏览器后台。', 'nerv-core' ) ),
				notice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setNotice( '' ); } }, notice ) : null,
				error ? el( Notice, { status: 'warning', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'nerv-control-tools-grid' },
					el(
						'div',
						{ className: 'nerv-control-tool-card' },
						el( 'span', null, __( 'Markdown 镜像', 'nerv-core' ) ),
						el( 'strong', null, String( form.markdown.cached ) + '/' + String( form.markdown.eligible ) ),
						el( 'small', null, form.markdown.dir || __( '缓存目录不可用。', 'nerv-core' ) ),
						el(
							Button,
							{ variant: 'secondary', isBusy: 'refresh_markdown' === running, disabled: !! running, onClick: function () { runAction( 'refresh_markdown' ); } },
							__( '刷新 Markdown 镜像', 'nerv-core' )
						)
					),
					el(
						'div',
						{ className: 'nerv-control-tool-card' },
						el( 'span', null, __( '相关文章缓存', 'nerv-core' ) ),
						el( 'strong', null, form.related.enabled ? __( '已启用', 'nerv-core' ) : __( '已停用', 'nerv-core' ) ),
						el( 'small', null, __( '清理加权相关文章引擎使用的 transient 缓存。', 'nerv-core' ) ),
						el(
							Button,
							{ variant: 'secondary', isBusy: 'flush_related' === running, disabled: !! running, onClick: function () { runAction( 'flush_related' ); } },
							__( '清理相关文章缓存', 'nerv-core' )
						)
					),
						el(
							'div',
							{ className: 'nerv-control-tool-card' },
							el( 'span', null, __( '合作伙伴健康', 'nerv-core' ) ),
						el( 'strong', null, 'ONLINE ' + form.partners.online + ' / SLOW ' + form.partners.slow + ' / OFFLINE ' + form.partners.offline ),
						el( 'small', null, String( form.partners.total ) + ' ' + __( '条合作伙伴记录已跟踪。', 'nerv-core' ) ),
						el(
							Button,
							{ variant: 'secondary', onClick: function () { props.onSelectTab( 'partners' ); } },
								__( '打开合作伙伴工具', 'nerv-core' )
							)
						),
						el(
							'div',
							{ className: 'nerv-control-tool-card' },
							el( 'span', null, __( 'WebP 图片优化', 'nerv-core' ) ),
							el( 'strong', null, form.images.webpEnabled ? __( '已启用', 'nerv-core' ) : __( '已停用', 'nerv-core' ) ),
							el( 'small', null, __( '上传 JPEG/PNG 后自动生成 WebP，分享卡片优先使用真实媒体图。', 'nerv-core' ) + ' Q' + String( form.images.webpQuality || 0 ) ),
							el( 'small', null, 'MEDIA ' + String( ( form.images.mediaQueue && form.images.mediaQueue.status ) || 'idle' ) + ' / PENDING ' + String( ( form.images.mediaQueue && form.images.mediaQueue.pending ) || 0 ) ),
							form.images.mediaQueue && form.images.mediaQueue.lastError ? el( 'small', null, String( form.images.mediaQueue.lastError ) ) : null,
							el( 'small', null, 'SOCIAL ' + String( ( form.images.queue && form.images.queue.status ) || 'idle' ) + ' / PENDING ' + String( ( form.images.queue && form.images.queue.pending ) || 0 ) ),
							el( 'small', null, form.images.socialDir || __( '社交封面目录不可用。', 'nerv-core' ) ),
							el(
								Button,
								{ variant: 'secondary', isBusy: 'refresh_media_webp' === running, disabled: !! running, onClick: function () { runAction( 'refresh_media_webp' ); } },
								__( '补齐媒体 WebP', 'nerv-core' )
							),
							el(
								Button,
								{ variant: 'secondary', isBusy: 'refresh_social_covers' === running, disabled: !! running, onClick: function () { runAction( 'refresh_social_covers' ); } },
								__( '生成分享 WebP', 'nerv-core' )
							)
						),
						el(
							'div',
							{ className: 'nerv-control-tool-card' },
						el( 'span', null, __( '演示内容', 'nerv-core' ) ),
						el(
							'strong',
							null,
							'PROJECTS ' + String( ( form.demo.counts && form.demo.counts.projects ) || 0 ) +
								' / POSTS ' + String( ( form.demo.counts && form.demo.counts.posts ) || 0 ) +
								' / PARTNERS ' + String( ( form.demo.counts && form.demo.counts.partners ) || 0 )
						),
						el( 'small', null, form.demo.ready ? __( '演示记录可用于前台验证。', 'nerv-core' ) : __( '导入首页、归档和 GEO 检查使用的本地演示记录。', 'nerv-core' ) ),
						el(
							'div',
							{ className: 'nerv-control-demo-summary nerv-control-demo-summary--' + ( demoResult.status || ( form.demo.ready ? 'pass' : 'warning' ) ) },
							el( 'span', null, __( '已创建', 'nerv-core' ) + ' ' + String( ( demoResult.summary && demoResult.summary.created ) || 0 ) ),
							el( 'span', null, __( '已更新', 'nerv-core' ) + ' ' + String( ( demoResult.summary && demoResult.summary.updated ) || 0 ) ),
							el( 'span', null, __( '失败', 'nerv-core' ) + ' ' + String( ( demoResult.summary && demoResult.summary.failed ) || 0 ) )
						),
						demoResult.steps && demoResult.steps.length ? el(
							'ul',
							{ className: 'nerv-control-demo-steps' },
							demoResult.steps.map( function ( step ) {
								return el(
									'li',
									{ className: 'is-' + ( step.state || 'warning' ), key: step.key || step.label },
									el( 'strong', null, step.label || '' ),
									el( 'span', null, ( step.state || 'warning' ).toUpperCase() ),
									el( 'small', null, step.detail || '' )
								);
							} )
						) : null,
						el(
							Button,
							{ variant: 'secondary', isBusy: 'import_demo' === running, disabled: !! running, onClick: function () { runAction( 'import_demo' ); } },
							__( '导入 / 刷新演示内容', 'nerv-core' )
						),
						el( 'code', null, form.demo.command || 'php bin/seed-demo.php /path/to/wp-load.php' )
					)
				),
				el(
					'div',
					{ className: 'nerv-control-build-box nerv-control-build-box--' + ( form.build.status || 'missing' ) },
					el( 'h4', null, __( '发布打包', 'nerv-core' ) ),
					el( 'p', null, form.build.complete
						? __( '本地 dist 目录已有 bundle、主题和插件包。', 'nerv-core' )
						: 'unavailable' === form.build.status
							? __( '包状态只能从本地 monorepo 读取；部署到 wp-content 的副本只保留 CLI 命令供参考。', 'nerv-core' )
							: __( '发布要求同时有拆分包和整合包；缺失包请在 monorepo 根目录构建。', 'nerv-core' ) ),
					form.build.packages && form.build.packages.length ? el(
						'ul',
						{ className: 'nerv-control-build-packages' },
						form.build.packages.map( function ( pkg ) {
							return el(
								'li',
								{ className: 'is-' + ( pkg.state || 'missing' ), key: pkg.key || pkg.label },
								el( 'strong', null, pkg.label || '' ),
								el( 'span', null, ( pkg.state || 'missing' ).toUpperCase() ),
								el( 'small', null, pkg.file ? pkg.file + ' · ' + ( pkg.size || '' ) + ' · ' + ( pkg.modified || '' ) : 'unavailable' === pkg.state ? __( '请从本地 monorepo 的 dist 目录检查包文件。', 'nerv-core' ) : __( 'dist 中未找到包。', 'nerv-core' ) )
							);
						} )
					) : null,
					el( 'code', null, form.build.commands && form.build.commands.split ? form.build.commands.split : './build.sh --split' ),
					el( 'code', null, form.build.commands && form.build.commands.bundle ? form.build.commands.bundle : './build.sh --bundle' ),
					el( 'small', null, form.build.available ? form.build.script : __( '构建脚本是本地 monorepo 工具，不会部署进 wp-content。', 'nerv-core' ) ),
					form.build.distDir ? el( 'small', null, __( 'Dist 目录：', 'nerv-core' ) + ' ' + form.build.distDir ) : null
				),
				el(
					'div',
					{ className: 'nerv-control-themecheck-box nerv-control-themecheck-box--' + ( themeCheck.status || 'pending' ) },
					el( 'h4', null, __( '主题检查 / 发布审计', 'nerv-core' ) ),
					el( 'p', null, themeCheck.message || __( '打包主题前运行本地发布审计。', 'nerv-core' ) ),
					el(
						'div',
						{ className: 'nerv-control-themecheck-summary' },
						el( 'span', null, __( '通过', 'nerv-core' ) + ' ' + String( ( themeCheck.summary && themeCheck.summary.pass ) || 0 ) ),
						el( 'span', null, __( '警告', 'nerv-core' ) + ' ' + String( ( themeCheck.summary && themeCheck.summary.warning ) || 0 ) ),
						el( 'span', null, __( '失败', 'nerv-core' ) + ' ' + String( ( themeCheck.summary && themeCheck.summary.fail ) || 0 ) )
					),
					themeCheck.checks && themeCheck.checks.length
						? el(
								'ul',
								{ className: 'nerv-control-themecheck-list' },
								themeCheck.checks.map( function ( check ) {
									return el(
										'li',
										{ className: 'is-' + ( check.state || 'warning' ), key: check.key || check.label },
										el( 'strong', null, check.label || '' ),
										el( 'span', null, ( check.state || 'warning' ).toUpperCase() ),
										el( 'small', null, check.detail || '' )
									);
								} )
						  )
						: el( 'small', null, __( '可用时使用官方 Theme Check；否则运行内置区块主题发布审计。', 'nerv-core' ) ),
					el(
						Button,
						{ variant: 'secondary', isBusy: 'run_theme_check' === running, disabled: !! running, onClick: function () { runAction( 'run_theme_check' ); } },
						__( '运行主题检查 / 发布审计', 'nerv-core' )
					)
				),
				el(
					'div',
					{ className: 'nerv-control-preset-box' },
					el( 'h4', null, __( '设置预设 JSON', 'nerv-core' ) ),
					el( 'p', null, __( '导出或导入白名单内的主题/控制台设置；不会包含 API 密钥。', 'nerv-core' ) ),
					el( 'small', null, ( form.preset.optionGroups || [] ).join( ', ' ) ),
					el( TextareaControl, {
						label: __( '预设 JSON', 'nerv-core' ),
						value: presetText,
						rows: 8,
						__nextHasNoMarginBottom: true,
						onChange: function ( value ) {
							setPresetText( value );
						},
					} ),
					el(
						'div',
						{ className: 'nerv-control-preset-actions' },
						el(
							Button,
							{ variant: 'secondary', isBusy: 'export_preset' === running, disabled: !! running, onClick: function () { runAction( 'export_preset' ); } },
							__( '导出预设 JSON', 'nerv-core' )
						),
						el(
							Button,
							{ variant: 'primary', isBusy: 'import_preset' === running, disabled: !! running || ! presetText.trim(), onClick: function () { runAction( 'import_preset', { preset: presetText } ); } },
							__( '导入预设 JSON', 'nerv-core' )
						)
					)
				),
				el(
					'div',
					{ className: 'nerv-control-actions' }
				)
			)
		);
	}

	function PlannedPanel( props ) {
		const tab = props.tab || {};
		return el(
			'div',
			{ className: 'nerv-control-tab-view' },
			el(
				'section',
				{ className: 'nerv-control-panel' },
				el( 'h3', null, tab.label || __( '主题控制页面', 'nerv-core' ) ),
				el( 'p', { className: 'nerv-control-form-note' }, __( 'React 页面外壳已就绪；可编辑控件会在后续从旧设置表单迁移。', 'nerv-core' ) )
			)
		);
	}

	function Dashboard( props ) {
		const data = props.data;
		const activeTab = props.activeTab;
		const singlePageMode = !! ( window.nervCoreControl && window.nervCoreControl.singlePageMode );
		const currentTitle = window.nervCoreControl && window.nervCoreControl.currentTitle ? window.nervCoreControl.currentTitle : '';
		const currentDescription = window.nervCoreControl && window.nervCoreControl.currentDescription ? window.nervCoreControl.currentDescription : '';
		const [ wizardRunning, setWizardRunning ] = useState( '' );
		const [ wizardNotice, setWizardNotice ] = useState( '' );
		const [ wizardError, setWizardError ] = useState( '' );
		const doneSteps = data.steps.filter( function ( step ) {
			return step.done;
		} ).length;
		const activeTabData = data.tabs.find( function ( tab ) {
			return tab.id === activeTab;
		} );
		let tabContent;

		function runWizardAction( action ) {
			setWizardRunning( action );
			setWizardNotice( '' );
			setWizardError( '' );
			apiFetch( {
				path: window.nervCoreControl ? window.nervCoreControl.toolsActionPath : '/nerv-core/v1/control-tools-action',
				method: 'POST',
				data: { toolAction: action },
			} )
				.then( function ( response ) {
					if ( response.dashboard ) {
						props.onDashboardUpdate( response.dashboard );
					}
					setWizardNotice( response.message || __( '启用步骤已完成。', 'nerv-core' ) );
				} )
				.catch( function ( response ) {
					setWizardError( response && response.message ? response.message : __( '启用步骤未能完成。', 'nerv-core' ) );
				} )
				.finally( function () {
					setWizardRunning( '' );
				} );
		}

		if ( 'dashboard' === activeTab ) {
			tabContent = el(
				'div',
				{ className: 'nerv-control-grid' },
				el(
					'section',
					{ className: 'nerv-control-panel nerv-control-panel--wide' },
					el(
						'div',
						{ className: 'nerv-control-panel__title' },
						el( 'h3', null, __( '健康信号', 'nerv-core' ) ),
						el( 'a', { href: data.legacy.anchor }, data.legacy.label )
					),
					el( 'ul', { className: 'nerv-control-health' }, data.health.map( healthRow ) )
				),
				el(
					'section',
					{ className: 'nerv-control-panel' },
					el(
						'div',
						{ className: 'nerv-control-panel__title' },
						el( 'h3', null, __( '启用向导', 'nerv-core' ) ),
						el( 'span', null, doneSteps + '/' + data.steps.length )
					),
					wizardNotice ? el( Notice, { status: 'success', isDismissible: true, onRemove: function () { setWizardNotice( '' ); } }, wizardNotice ) : null,
					wizardError ? el( Notice, { status: 'warning', isDismissible: true, onRemove: function () { setWizardError( '' ); } }, wizardError ) : null,
					el( 'ol', { className: 'nerv-control-steps' }, data.steps.map( function ( step, index ) {
						return stepItem( step, index, {
							running: wizardRunning,
							onAction: runWizardAction,
							onSelectTab: props.onSelectTab,
						} );
					} ) )
				),
				activityRows( __( '最近 AI 抓取', 'nerv-core' ), data.activity.crawlers, function ( row, index ) {
					return el(
						'li',
						{ key: index },
						el( 'strong', null, row.label || row.bot || '' ),
						el( 'span', null, row.title || row.url || '' ),
						el( 'small', null, row.time || '' )
					);
				} ),
				activityRows( __( 'IndexNow 日志', 'nerv-core' ), data.activity.indexnow, function ( row, index ) {
					return el(
						'li',
						{ key: index },
						el( 'strong', null, ( row.status || '' ).toUpperCase() ),
						el( 'span', null, row.message || '' ),
						el( 'small', null, row.time || '' )
					);
				} ),
				el(
					'section',
					{ className: 'nerv-control-panel nerv-control-links' },
					el( 'h3', null, __( '机器可读资源', 'nerv-core' ) ),
					el(
						'div',
						null,
						Object.keys( data.links ).map( function ( key ) {
							return el( 'a', { href: data.links[ key ], key: key, target: '_blank', rel: 'noreferrer' }, key );
						} )
					)
				)
			);
		} else if ( 'ai' === activeTab ) {
			tabContent = el( AiServicesPanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else if ( 'brand' === activeTab ) {
			tabContent = el( BrandPanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else if ( 'seo' === activeTab ) {
			tabContent = el( SeoPanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else if ( 'layout' === activeTab ) {
			tabContent = el( PanelsPanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else if ( 'mobile' === activeTab ) {
			tabContent = el( MobilePanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else if ( 'social' === activeTab ) {
			tabContent = el( SocialPanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else if ( 'articles' === activeTab ) {
			tabContent = el( ArticlesPanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else if ( 'geo' === activeTab ) {
			tabContent = el( GeoPanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else if ( 'appearance' === activeTab ) {
			tabContent = el( AppearancePanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else if ( 'effects' === activeTab ) {
			tabContent = el( EffectsPanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else if ( 'tools' === activeTab ) {
			tabContent = el( ToolsPanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else if ( 'partners' === activeTab ) {
			tabContent = el( PartnersPanel, { data: data, onDashboardUpdate: props.onDashboardUpdate, onSelectTab: props.onSelectTab } );
		} else {
			tabContent = el( PlannedPanel, { tab: activeTabData, onSelectTab: props.onSelectTab } );
		}

		return el(
			'div',
			{ className: 'nerv-control-shell' + ( singlePageMode ? ' nerv-control-single-page' : '' ) },
			el(
				'header',
				{ className: 'nerv-control-hero' },
				el(
					'div',
					null,
					el( 'span', { className: 'nerv-control-kicker' }, singlePageMode ? __( '主题设置页面', 'nerv-core' ) : __( '主题总览', 'nerv-core' ) ),
					el( 'h2', null, currentTitle || data.site.name || __( 'NERV Terminal', 'nerv-core' ) ),
					el( 'p', null, currentDescription || __( '在一个控制台管理 GEO、AI 服务、爬虫可见性、合作伙伴和主题运维。', 'nerv-core' ) )
				),
				el(
					'div',
					{ className: 'nerv-control-identity' },
					el( 'span', null, __( '主题', 'nerv-core' ) ),
					el( 'strong', null, data.site.theme || 'NERV Terminal' ),
					el( 'small', null, 'WP ' + data.site.wpVersion + ' / Core ' + data.site.core )
				)
			),
			singlePageMode ? null : el(
				'nav',
				{ className: 'nerv-control-tabs', 'aria-label': __( '主题控制页面', 'nerv-core' ) },
				data.tabs.map( function ( tab ) {
					return tabButton( tab, activeTab, props.onSelectTab );
				} )
			),
				el( 'section', { className: 'nerv-control-metrics' }, data.metrics.map( metricCard ) ),
				tabContent
			);
		}

	function App() {
		const [ data, setData ] = useState( null );
		const [ error, setError ] = useState( '' );
		const [ activeTab, setActiveTab ] = useState( window.nervCoreControl && window.nervCoreControl.initialTab ? window.nervCoreControl.initialTab : 'dashboard' );

		useEffect( function () {
			if ( window.nervCoreControl && window.nervCoreControl.nonce ) {
				apiFetch.use( apiFetch.createNonceMiddleware( window.nervCoreControl.nonce ) );
			}
			apiFetch( { path: window.nervCoreControl ? window.nervCoreControl.restPath : '/nerv-core/v1/control-dashboard' } )
				.then( function ( response ) {
					setData( response );
				} )
				.catch( function () {
					setError( __( '主题控制台数据加载失败。', 'nerv-core' ) );
				} );
		}, [] );

		if ( error ) {
			return el( Notice, { status: 'warning', isDismissible: false }, error );
		}

		if ( ! data ) {
			return el(
				'div',
				{ className: 'nerv-control-shell nerv-control-shell--loading' },
				el( Spinner, null ),
				el( 'p', null, __( '正在加载主题控制台...', 'nerv-core' ) )
			);
		}

		return el( Dashboard, { data: data, activeTab: activeTab, onSelectTab: setActiveTab, onDashboardUpdate: setData } );
	}

	function boot() {
		const mount = document.getElementById( 'nerv-control-app' );
		if ( ! mount ) {
			return;
		}

		if ( element.createRoot ) {
			element.createRoot( mount ).render( el( App ) );
			return;
		}

		element.render( el( App ), mount );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )( window.wp.apiFetch, window.wp.components, window.wp.element, window.wp.i18n );
