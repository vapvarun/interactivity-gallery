/**
 * Interactivity Gallery - Block Editor Script
 */
(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var __ = wp.i18n.__;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var PanelBody = wp.components.PanelBody;
    var RangeControl = wp.components.RangeControl;
    var TextControl = wp.components.TextControl;
    var ServerSideRender = wp.serverSideRender;

    registerBlockType('interactivity-gallery/gallery', {
        title: __('Interactivity Gallery', 'interactivity-gallery'),
        icon: 'format-gallery',
        category: 'media',
        description: __('Display attached media with lightbox and pagination', 'interactivity-gallery'),
        
        attributes: {
            postId: {
                type: 'number',
                default: 0
            },
            perPage: {
                type: 'number',
                default: 12
            },
            columns: {
                type: 'number',
                default: 3
            }
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps();
            
            return wp.element.createElement(
                'div',
                blockProps,
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Gallery Settings', 'interactivity-gallery') },
                        wp.element.createElement(TextControl, {
                            label: __('Post ID', 'interactivity-gallery'),
                            value: attributes.postId || '',
                            onChange: function(value) {
                                setAttributes({ postId: parseInt(value) || 0 });
                            },
                            help: __('Leave empty or 0 to use current post', 'interactivity-gallery')
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Items per page', 'interactivity-gallery'),
                            value: attributes.perPage,
                            onChange: function(value) {
                                setAttributes({ perPage: value });
                            },
                            min: 1,
                            max: 50
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Columns', 'interactivity-gallery'),
                            value: attributes.columns,
                            onChange: function(value) {
                                setAttributes({ columns: value });
                            },
                            min: 1,
                            max: 6
                        })
                    )
                ),
                wp.element.createElement(
                    'div',
                    { className: 'interactivity-gallery-block-preview' },
                    wp.element.createElement(ServerSideRender, {
                        block: 'interactivity-gallery/gallery',
                        attributes: attributes
                    })
                )
            );
        },
        
        // Dynamic block - rendered on server
        save: function() {
            return null;
        }
    });
})(window.wp);