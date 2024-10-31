jQuery(function(){
	( function( blocks, element ) {
		var el = element.createElement,
			el2 = element.createElement,
			el3 = element.createElement,
			el4 = element.createElement,
			el6 = element.createElement,
			InspectorControls = ('blockEditor' in wp) ? wp.blockEditor.InspectorControls : wp.editor.InspectorControls,
            RadioControl = wp.components.RadioControl;

		/* Plugin Category */
		blocks.getCategories().push({slug: 'cpms', title: 'Music Store'});

		/* ICONS */
		const iconCPMS = el('img', { width: 20, height: 20, src:  "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAIAAADZrBkAAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAsSAAALEgHS3X78AAAAHnRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M1LjGrH0jrAAAAFnRFWHRDcmVhdGlvbiBUaW1lADEwLzA2LzEzdw7Y2QAAADVJREFUKJFj/M/QwkA6YCJDz4Bp+1+NIIkAjFQKEmRrcZHD0zYYoLVtqIC6tmGyaWMbTbUBACQXL53t1JHjAAAAAElFTkSuQmCC" } );

		const iconCPMSP = el2('img', { width: 20, height: 20, src:  "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAIAAADZrBkAAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAsSAAALEgHS3X78AAAAHnRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M1LjGrH0jrAAAAFnRFWHRDcmVhdGlvbiBUaW1lADEwLzA2LzEzdw7Y2QAAAHpJREFUKJFj/M/QwkA6YCJDD+20LfAlXdsCX4Z4PQYGBob/1QiSgYGBgYGRNkGCZAMp2hhbh5Ftd7IQJLG23cliUBZkYGBgUBaE6vxfjTXGCcXbAl+GhM1E2AZPEBDbsOnB0KYyjeHuewYGBoa77xlUpuFxBI0SFw4AALPuJw5qFcdxAAAAAElFTkSuQmCC" } );

		const iconCPMSL = el3('img', { width: 20, height: 20, src:  "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAIAAADZrBkAAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAsSAAALEgHS3X78AAAAHnRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M1LjGrH0jrAAAAFnRFWHRDcmVhdGlvbiBUaW1lADEwLzA2LzEzdw7Y2QAAAD5JREFUKJFj/M/QwkA6YCJDz0Bq+1/N8L8aysBDMjAwMDAwIoIEIsrYSoxtLAgmcRowHEkKGPXbkPcbHbQBADMcLMe5LB6/AAAAAElFTkSuQmCC" } );

		const iconCPMSC = el4('img', { width: 20, height: 20, src:  "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAIAAADZrBkAAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAsSAAALEgHS3X78AAAAHnRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M1LjGrH0jrAAAAFnRFWHRDcmVhdGlvbiBUaW1lADEwLzA2LzEzdw7Y2QAAADBJREFUKJFj/M/QwkA6YCJDD9W1/a/GzoYBRqr6jfa2QUwlhhz1G37bkMFwSyWkAACtTC+lju8gtwAAAABJRU5ErkJggg==" } );

		const iconCPMSPL = el6('img', { width: 20, height: 20, src:  "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAV/QAAFf0BzXBRYQAAABZ0RVh0Q3JlYXRpb24gVGltZQAxMC8xOS8yMdcu09AAAAAcdEVYdFNvZnR3YXJlAEFkb2JlIEZpcmV3b3JrcyBDUzbovLKMAAAASklEQVQ4jWP8z9Dyn4GKgImahhFv4P9qKhvI2EqhgTAXEaKx2U2fSEF3ASE+EhgkLsQD6OzCwRvL6GDoxfLgceHQjWWKDSShPAQAIq89G/ormYQAAAAASUVORK5CYII=" } );

		function esc_regexp(str)
		{
			return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
		};

		function get_attr_value(attr, shortcode)
		{
			var reg = new RegExp('\\b'+esc_regexp(attr)+'\\s*=\\s*[\'"]([^\'"]*)[\'"]', 'i'),
				res = reg.exec(shortcode);
			if(res !== null) return res[1];
			return '';
		};

		function generate_shortcode(shortcode, attr, value, props)
		{
			var shortcode = wp.shortcode.next(shortcode, props.attributes.shortcode),
				attrs = shortcode.shortcode.attrs.named;

			shortcode.shortcode.attrs.named[attr] = value;
			props.setAttributes({'shortcode': shortcode.shortcode.string()});
		};

		/* Music Store Shortcode */
		blocks.registerBlockType( 'cpms/music-store', {
			title: 'Music Store',
			icon: iconCPMS,
			category: 'cpms',
			supports: {
				customClassName	: false,
				className		: false
			},
			attributes: {
				shortcode : {
					type 	: 'string',
					default	: '[music_store columns="1"]'
				}
			},

			edit: function( props ) {
				var focus = props.isSelected,
					children = [];

				// Editor
				children.push(
					el(
						'div', {className: 'ms-iframe-container', key: 'ms_iframe_container'},
						el('div', {className: 'ms-iframe-overlay', key: 'ms_iframe_overlay'}),
						el('iframe',
							{
								key: 'ms_store_iframe',
								src: ms_ge_config.url+encodeURIComponent(props.attributes.shortcode),
								height: 0,
								width: '100%',
								scrolling: 'no'
							}
						)
					)
				);

				// InspectorControls
				if(!!focus)
				{
					children.push(
						el(
							InspectorControls,
							{
								key: 'ms_inspector'
							},
							[
								el('hr', {key: 'ms_hr'}),
                                el(
                                    'div',
                                    {
                                        key: 'cp_inspector_container',
                                        style:{paddingLeft:'20px',paddingRight:'20px'}
                                    },
                                    [
                                        // Exclude
                                        el(
                                            'label',
                                            {
                                                htmlFor: 'ms_products_to_exclude',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_products_to_exclude_label'
                                            },
                                            ms_ge_config.labels.exclude
                                        ),
                                        el(
                                            'input',
                                            {
                                                key: 'ms_products_to_exclude',
                                                type: 'text',
                                                style: {width:'100%'},
                                                value : get_attr_value('exclude', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store', 'exclude', evt.target.value, props);}
                                            }
                                        ),
                                        el(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_products_to_exclude_help'
                                            },
                                            ms_ge_config.help.exclude
                                        ),

                                        // Columns
                                        el(
                                            'label',
                                            {
                                                htmlFor: 'ms_columns',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_columns_label'
                                            },
                                            ms_ge_config.labels.columns
                                        ),
                                        el(
                                            'input',
                                            {
                                                key: 'ms_columns',
                                                type: 'number',
                                                style: {width:'100%'},
                                                value : get_attr_value('columns', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store', 'columns', evt.target.value, props);}
                                            }
                                        ),
                                        el(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_columns_help'
                                            },
                                            ms_ge_config.help.columns
                                        ),

                                        // Genres
                                        el(
                                            'label',
                                            {
                                                htmlFor: 'ms_genres',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_genres_label'
                                            },
                                            ms_ge_config.labels.genres
                                        ),
                                        el(
                                            'input',
                                            {
                                                key: 'ms_genres',
                                                type: 'text',
                                                style: {width:'100%'},
                                                value : get_attr_value('genre', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store', 'genre', evt.target.value, props);}
                                            }
                                        ),
                                        el(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_genres_help'
                                            },
                                            ms_ge_config.help.genres
                                        ),

                                        // Artists
                                        el(
                                            'label',
                                            {
                                                htmlFor: 'ms_artists',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_artists_label'
                                            },
                                            ms_ge_config.labels.artists
                                        ),
                                        el(
                                            'input',
                                            {
                                                key: 'ms_artists',
                                                type: 'text',
                                                style: {width:'100%'},
                                                value : get_attr_value('artist', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store', 'artist', evt.target.value, props);}
                                            }
                                        ),
                                        el(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_artists_help'
                                            },
                                            ms_ge_config.help.artists
                                        ),

                                        // Albums
                                        el(
                                            'label',
                                            {
                                                htmlFor: 'ms_albums',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_albums_label'
                                            },
                                            ms_ge_config.labels.albums
                                        ),
                                        el(
                                            'input',
                                            {
                                                key: 'ms_albums',
                                                type: 'text',
                                                style: {width:'100%'},
                                                value : get_attr_value('album', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store', 'album', evt.target.value, props);}
                                            }
                                        ),
                                        el(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_albums_help'
                                            },
                                            ms_ge_config.help.albums
                                        )
                                    ]
                                )
							]
						)
					);
				}
				return [children];
			},

			save: function( props ) {
				return props.attributes.shortcode;
			}
		});

        /* Music Store Product Shortcode */
		blocks.registerBlockType( 'cpms/music-store-product', {
			title: 'Product',
			icon: iconCPMSP,
			category: 'cpms',
			supports: {
				customClassName	: false,
				className		: false
			},
			attributes: {
				shortcode : {
					type 		: 'string',
					default		: '[music_store_product id=""]'
				}
			},
			edit: function( props ) {
				var focus = props.isSelected,
					children = [],
					options = [],
					layout = get_attr_value('layout', props.attributes.shortcode),
					id = get_attr_value('id', props.attributes.shortcode),
					default_layout = '';

				// Populate options
				if(/^\s*$/.test(layout)) layout = 'store';
				for(var i in ms_ge_config.layout)
				{
					var key = 'ms_product_layout_'+i,
						config = {key: key, value: i};

					if(default_layout == '') default_layout = i;
					if(i == layout) default_layout = i;
					options.push(el2('option', config, ms_ge_config.layout[i]));
				}

				// Editor
				if(/^\s*$/.test(id))
				{
					children.push(
						el2(
							'div', {key: 'ms_product'}, ms_ge_config.labels.product_required
						)
					);
				}
				else
				{
					children.push(
						el2(
							'div', {className: 'ms-iframe-container', key: 'ms_iframe_container'},
							el2('div', {className: 'ms-iframe-overlay', key: 'ms_iframe_overlay'}),
							el2('iframe',
								{
									key: 'ms_product_iframe',
									src: ms_ge_config.url+encodeURIComponent(props.attributes.shortcode),
									height: 0,
									width: '100%',
									scrolling: 'no'
								}
							)
						)
					);
				}

				// InspectorControls
				if(!!focus)
				{
					children.push(
						el2(
							InspectorControls,
							{
								key: 'ms_inspector'
							},
							[
								el2('hr', {key: 'ms_hr'}),
                                el2(
                                    'div',
                                    {
                                        key: 'cp_inspector_container',
                                        style:{paddingLeft:'20px',paddingRight:'20px'}
                                    },
                                    [
                                        // Product
                                        el2(
                                            'label',
                                            {
                                                htmlFor: 'ms_product',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_product_label'
                                            },
                                            ms_ge_config.labels.product
                                        ),
                                        el2(
                                            'input',
                                            {
                                                key: 'ms_product',
                                                type: 'number',
                                                style: {width:'100%'},
                                                value : get_attr_value('id', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store_product', 'id', evt.target.value, props);}
                                            }
                                        ),
                                        el2(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_product_help'
                                            },
                                            ms_ge_config.help.product
                                        ),

                                        // Layouts
                                        el2(
                                            'label',
                                            {
                                                htmlFor: 'ms_product_layout',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_product_layout_label'
                                            },
                                            ms_ge_config.labels.layout
                                        ),
                                        el2(
                                            'select',
                                            {
                                                key: 'ms_product_layout',
                                                style: {width:'100%'},
                                                onChange : function(evt){generate_shortcode('music_store_product', 'layout', evt.target.value, props);},
                                                value: default_layout
                                            },
                                            options
                                        ),
                                        el2(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_product_layout_help'
                                            },
                                            ms_ge_config.help.layout
                                        )
                                    ]
                                )
							]
						)
					);
				}

				return [children];
			},
			save: function( props ) {
				return props.attributes.shortcode;
			}
		});

        /* Music Store Products List Shortcode */
		blocks.registerBlockType( 'cpms/music-store-products-lst', {
			title: 'Products List',
			icon: iconCPMSL,
			category: 'cpms',
			supports: {
				customClassName	: false,
				className		: false
			},
			attributes: {
				shortcode : {
					type 		: 'string',
					default		: '[music_store_product_list columns="1" number="3" type="top_rated"]'
				}
			},
			edit: function( props ) {
				var focus = props.isSelected,
					children = [],
					list_options = [],
					products_type = get_attr_value('products', props.attributes.shortcode),
					list_type 	  = get_attr_value('type', props.attributes.shortcode),
					default_list_type = '',
					default_products_type = '';

				// Populate list_options
				if(/^\s*$/.test(list_type)) list_type = 'new_products';
				for(var i in ms_ge_config.list_types)
				{
					var key = 'ms_list_type_'+i,
						config = {key: key, value: i};

					if(default_list_type == '') default_list_type = i;
					if(i == list_type) default_list_type = i;
					list_options.push(el3('option', config, ms_ge_config.list_types[i]));
				}

				// Editor
				children.push(
					el3(
						'div', {className: 'ms-iframe-container', key: 'ms_iframe_container'},
						el3('div', {className: 'ms-iframe-overlay', key: 'ms_iframe_overlay'}),
						el3('iframe',
							{
								key: 'ms_products_list_iframe',
								src: ms_ge_config.url+encodeURIComponent(props.attributes.shortcode),
								height: 0,
								width: '100%',
								scrolling: 'no'
							}
						)
					)
				);

				// InspectorControls
				if(!!focus)
				{
					children.push(
						el3(
							InspectorControls,
							{
								key: 'ms_inspector'
							},
							[
								el3('hr', {key: 'ms_hr'}),
                                el3(
                                    'div',
                                    {
                                        key: 'cp_inspector_container',
                                        style:{paddingLeft:'20px',paddingRight:'20px'}
                                    },
                                    [
                                        // List type
                                        el3(
                                            'label',
                                            {
                                                htmlFor: 'ms_list_types',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_list_types_label'
                                            },
                                            ms_ge_config.labels.list_types
                                        ),
                                        el3(
                                            'select',
                                            {
                                                key: 'ms_list_types',
                                                style: {width:'100%'},
                                                onChange : function(evt){generate_shortcode('music_store_product_list', 'type', evt.target.value, props);},
                                                value: default_list_type
                                            },
                                            list_options
                                        ),
                                        el3(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_list_types_help'
                                            },
                                            ms_ge_config.help.list_types
                                        ),

                                        // Columns
                                        el3(
                                            'label',
                                            {
                                                htmlFor: 'ms_columns',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_columns_label'
                                            },
                                            ms_ge_config.labels.columns
                                        ),
                                        el3(
                                            'input',
                                            {
                                                key: 'ms_columns',
                                                type: 'number',
                                                style: {width:'100%'},
                                                value : get_attr_value('columns', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store_product_list', 'columns', evt.target.value, props);}
                                            }
                                        ),
                                        el3(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_columns_help'
                                            },
                                            ms_ge_config.help.columns
                                        ),

                                        // Number
                                        el3(
                                            'label',
                                            {
                                                htmlFor: 'ms_number',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_number_label'
                                            },
                                            ms_ge_config.labels.number_of_products
                                        ),
                                        el3(
                                            'input',
                                            {
                                                key: 'ms_number',
                                                type: 'number',
                                                style: {width:'100%'},
                                                value : get_attr_value('number', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store_product_list', 'number', evt.target.value, props);}
                                            }
                                        ),
                                        el3(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_number_help'
                                            },
                                            ms_ge_config.help.number_of_products
                                        ),

                                        // Genres
                                        el3(
                                            'label',
                                            {
                                                htmlFor: 'ms_genres',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_genres_label'
                                            },
                                            ms_ge_config.labels.genres
                                        ),
                                        el3(
                                            'input',
                                            {
                                                key: 'ms_genres',
                                                type: 'text',
                                                style: {width:'100%'},
                                                value : get_attr_value('genre', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store_product_list', 'genre', evt.target.value, props);}
                                            }
                                        ),
                                        el3(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_genres_help'
                                            },
                                            ms_ge_config.help.genres
                                        ),

                                        // Artists
                                        el3(
                                            'label',
                                            {
                                                htmlFor: 'ms_artists',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_artists_label'
                                            },
                                            ms_ge_config.labels.artists
                                        ),
                                        el3(
                                            'input',
                                            {
                                                key: 'ms_artists',
                                                type: 'text',
                                                style: {width:'100%'},
                                                value : get_attr_value('artist', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store_product_list', 'artist', evt.target.value, props);}
                                            }
                                        ),
                                        el3(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_artists_help'
                                            },
                                            ms_ge_config.help.artists
                                        ),

                                        // Albums
                                        el3(
                                            'label',
                                            {
                                                htmlFor: 'ms_albums',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_albums_label'
                                            },
                                            ms_ge_config.labels.albums
                                        ),
                                        el3(
                                            'input',
                                            {
                                                key: 'ms_albums',
                                                type: 'text',
                                                style: {width:'100%'},
                                                value : get_attr_value('album', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store_product_list', 'album', evt.target.value, props);}
                                            }
                                        ),
                                        el3(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_albums_help'
                                            },
                                            ms_ge_config.help.albums
                                        )
                                    ]
                                )
							]
						)
					);
				}
				return [children];
			},
			save: function( props ) {
				return props.attributes.shortcode;
			}
		});

		/* Music Store Purchased List Shortcode */
		blocks.registerBlockType( 'cpms/music-store-purchased-list', {
			title: 'Purchased List',
			icon: iconCPMSPL,
			category: 'cpms',
			supports: {
				customClassName	: false,
				className		: false
			},
			attributes: {
				shortcode : {
					type 	 : 'string',
					default	 : '[music_store_purchased_list]'
				}
			},
			edit: function( props ) {
				var focus = props.isSelected,
					children = [];

				// Editor
				children.push(
					el6(
						'div', {className: 'ms-iframe-container', key: 'ms_iframe_container'},
						el6('div', {className: 'ms-iframe-overlay', key: 'ms_iframe_overlay'}),
						el6('iframe',
							{
								key: 'ms_purchased_list_iframe',
								src: ms_ge_config.url+encodeURIComponent(props.attributes.shortcode),
								height: 0,
								width: '100%',
								scrolling: 'no'
							}
						)
					)
				);
				return [children];
			},
			save: function( props ) {
				return props.attributes.shortcode;
			}
		});

        /* Music Store Sales Counter Shortcode */
        blocks.registerBlockType( 'cpms/music-store-sales-counter', {
            title: 'Sales Counter',
            icon: iconCPMSC,
            category: 'cpms',
            supports: {
                customClassName	: false,
                className		: false
            },
            attributes: {
                shortcode : {
                    type 		: 'string',
                    default		: '[music_store_sales_counter min_length="3" style="digits"]'
                }
            },
            edit: function( props ) {
                var focus = props.isSelected,
                    children = [],
                    options = [],
                    numbers_styles = get_attr_value('style', props.attributes.shortcode);

                // Populate options
                if(/^\s*$/.test(numbers_styles)) numbers_styles = 'alt_digits';
                for(var i in ms_ge_config.numbers_styles)
                {
                    var iconos = [];
                    for(var j = 0; j < 4; j++)
                    {
                        iconos.push(el4('img',{src:ms_ge_config.numbers_styles[i]+j+'.gif', key: 'ms_number_style_'+i+'_'+j}));
                    }
                    options.push({label: iconos, value: i, key: 'ms_number_style_'+i});
                }

                // Editor
                children.push(
                    el4(
                        'div', {className: 'ms-iframe-container', key: 'ms_iframe_container'},
                        el4('div', {className: 'ms-iframe-overlay', key: 'ms_iframe_overlay'}),
                        el4('iframe',
                            {
                                key: 'ms_counter_iframe',
                                src: ms_ge_config.url+encodeURIComponent(props.attributes.shortcode),
                                height: 0,
                                width: '100%',
                                scrolling: 'no'
                            }
                        )
                    )
                );

                // InspectorControls
                if(!!focus)
                {
                    children.push(
                        el4(
                            InspectorControls,
                            {
                                key: 'ms_inspector'
                            },
                            [
                                el4('hr', {key: 'ms_hr'}),
                                el4(
                                    'div',
                                    {
                                        key: 'cp_inspector_container',
                                        style:{paddingLeft:'20px',paddingRight:'20px'}
                                    },
                                    [
                                        // Styles
                                        el4(
                                            'label',
                                            {
                                                htmlFor: 'ms_numbers_styles',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_numbers_styles_label'
                                            },
                                            ms_ge_config.labels.numbers_styles
                                        ),
                                        el4(
                                            RadioControl,
                                            {
                                                key: 'ms_numbers_styles',
                                                help: ms_ge_config.help.numbers_styles,
                                                selected: numbers_styles,
                                                options: options,
                                                onChange : function(value){generate_shortcode('music_store_sales_counter', 'style', value, props);}
                                            }
                                        ),

                                        // Length
                                        el4(
                                            'label',
                                            {
                                                htmlFor: 'ms_number_of_digits',
                                                style:{fontWeight:'bold'},
                                                key: 'ms_number_of_digits_label'
                                            },
                                            ms_ge_config.labels.number_of_digits
                                        ),
                                        el4(
                                            'input',
                                            {
                                                key: 'ms_number_of_digits',
                                                type: 'number',
                                                style: {width:'100%'},
                                                value: get_attr_value('min_length', props.attributes.shortcode),
                                                onChange : function(evt){generate_shortcode('music_store_sales_counter', 'min_length', evt.target.value, props);}
                                            }
                                        ),
                                        el4(
                                            'div',
                                            {
                                                style: {fontStyle: 'italic'},
                                                key: 'ms_number_of_digits_help'
                                            },
                                            ms_ge_config.help.number_of_digits
                                        )
                                    ]
                                )
                            ]
                        )
                    );
                }

                return [children];
            },
            save: function( props ) {
                return props.attributes.shortcode;
            }
        });
	})(
		window.wp.blocks,
		window.wp.element
	);

});