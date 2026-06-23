( function ( window, components, element, i18n ) {
	const el = element.createElement;
	const __ = i18n.__;
	const Button = components.Button;
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

	window.nervCoreControlUtils = {
		statusClass: statusClass,
		metricCard: metricCard,
		healthRow: healthRow,
		tabButton: tabButton,
		stepItem: stepItem,
		activityRows: activityRows,
		aiProviderDefaults: aiProviderDefaults,
		cloneAiFeature: cloneAiFeature,
		cloneAiServicesForm: cloneAiServicesForm,
		cloneBrandForm: cloneBrandForm,
		cloneSeoForm: cloneSeoForm,
		clonePanelsForm: clonePanelsForm,
		normalizePanelOrder: normalizePanelOrder,
		normalizeCustomPanelOrder: normalizeCustomPanelOrder,
		reorderPanels: reorderPanels,
		cloneGeoForm: cloneGeoForm,
		cloneEffectsForm: cloneEffectsForm,
		cloneAppearanceForm: cloneAppearanceForm,
		effectsSettingsPayload: effectsSettingsPayload,
		effectsPresetDocument: effectsPresetDocument,
		mergeEffectsPresetDocument: mergeEffectsPresetDocument,
		cloneArticlesForm: cloneArticlesForm,
		cloneMobileForm: cloneMobileForm,
		cloneSocialForm: cloneSocialForm,
		clonePartnersForm: clonePartnersForm,
		cloneToolsForm: cloneToolsForm,
	};
}( window, window.wp.components, window.wp.element, window.wp.i18n ) );
