(function(wp) {
  var registerBlockType = wp.blocks.registerBlockType;
  var InspectorControls = wp.editor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var TextControl = wp.components.TextControl;
  var Dashicon = wp.components.Dashicon;
  var el = wp.element.createElement;
  var withState = wp.compose.withState;
  var __ = wp.i18n.__;
function GshtControl(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var setState = props.setState;
    var status = props.status;
    var acc_url = attributes.acc_url === null ? '' : attributes.acc_url;
	var acc_title = attributes.acc_title === null ? '' : attributes.acc_title;
	var acc_content = attributes.acc_content === null ? '' : attributes.acc_content;	
	var acc_start = attributes.acc_start === null ? '' : attributes.acc_start;	
	var acc_total = attributes.acc_total === null ? '' : attributes.acc_total;
	var acc_location = attributes.acc_location === null ? '' : attributes.acc_location;

	function onValidateUrl(result) {
      setState({status: result.message});
    }

var inspectorControl = el(InspectorControls, {}, 
      el('p', {}, ''),
		el(TextControl, {
        label: 'Google Spreadsheet Url:',
        value: acc_url,
        onChange: function(value) {
          setAttributes({acc_url: value});          
        }
      }),
      el(TextControl, {
        label: 'Sheet column for accordion title',
        value: acc_title,
        onChange: function(value) {
          setAttributes({acc_title: value});          
        }
      })
	  ,
      el(TextControl, {
        label: 'Sheet column for accordion content',
        value: acc_content,
        onChange: function(value) {
          setAttributes({acc_content: value});          
        }
      })
	  ,
      el(TextControl, {
        label: 'Sheet row to start data',
        value: acc_start,
        onChange: function(value) {
          setAttributes({acc_start: value});          
        }
      })
	  ,
      el(TextControl, {
        label: 'Sheet total rows to get',
        value: acc_total,
        onChange: function(value) {
          setAttributes({acc_total: value});          
        }
      })
	  ,
      el(TextControl, {
        label: 'Sheet location',
        value: acc_location,
        onChange: function(value) {
          setAttributes({acc_location: value});          
        }
      })
	  ,
      el(PanelBody, {title: 'Help', initialOpen: false},
        el('p', {}, 'Accordion Title: use + between to marge (column1+column2)'),
        el('p', {}, 'Accordion Content: use + between to marge (column1+column2)'),
        el('p', {}, 'Start: use 2 sometime first row is title'),
        el('p', {}, 'Total: use -1 or all')
      )
    );
return el('div', {
        className: 'gsht-store-block',
        style: {
          backgroundColor: '#46aaf8',
          color: '#ffffff',
          padding: '20px',
        }
      },
     // el('img', {src: Gsht.pluginsUrl + '/images/btngshton.png', className: 'logo'}),
      el('p', {className: 'strong'}, 'GoogleSheet to Accordion'),
      el('p', {className: 'italic'}, 'Display google spreadsheet into the accordion format'),
      inspectorControl
    );
  }
registerBlockType('google-sheet/accordion', {
    title: __('GoogleSheet to Accordion'),
    category: 'embed',
    icon: {
      foreground: '#46aaf8',
      src: 'editor-insertmore'
    },
    attributes: {
      acc_url: {
        type: 'string',
        default: null
      },
      acc_title: {
        type: 'string',
        default: 'column1'
      },
      acc_content: {
        type: 'string',
        default: 'column2'
      },
      acc_start: {
        type: 'string',
        default: '1'
      },
      acc_total: {
        type: 'string',
        default: '-1'
      },
      acc_location: {
        type: 'string',
        default: 'Sheet1'
      }
    },
    edit: withState({status: ''})(GshtControl),
    save: function(props) {
      return null;
    }
  });
})(window.wp);