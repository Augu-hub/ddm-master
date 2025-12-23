export default function CustomPalette(palette, create, elementFactory, spaceTool, lassoTool, handTool, globalConnect, translate) {
  this._create = create;
  this._elementFactory = elementFactory;
  this._spaceTool = spaceTool;
  this._lassoTool = lassoTool;
  this._handTool = handTool;
  this._globalConnect = globalConnect;
  this._translate = translate;

  palette.registerProvider(this);
}

CustomPalette.$inject = [
  'palette',
  'create',
  'elementFactory',
  'spaceTool',
  'lassoTool',
  'handTool',
  'globalConnect',
  'translate'
];

CustomPalette.prototype.getPaletteEntries = function() {
  const {
    _create: create,
    _elementFactory: elementFactory,
    _spaceTool: spaceTool,
    _lassoTool: lassoTool,
    _handTool: handTool,
    _globalConnect: globalConnect,
    _translate: translate
  } = this;

  function createAction(type, group, className, title, options) {
    function createListener(event) {
      const shape = elementFactory.createShape({ type: type, ...options });
      create.start(event, shape);
    }

    return {
      group: group,
      className: className,
      title: title || translate('Create ' + type),
      action: {
        dragstart: createListener,
        click: createListener
      }
    };
  }

  return {
    'hand-tool': {
      group: 'tools',
      className: 'bpmn-icon-hand-tool',
      title: translate('Hand tool'),
      action: {
        click: function() { handTool.activateHand(event); }
      }
    },
    'lasso-tool': {
      group: 'tools',
      className: 'bpmn-icon-lasso-tool',
      title: translate('Lasso tool'),
      action: {
        click: function() { lassoTool.activateSelection(event); }
      }
    },
    'space-tool': {
      group: 'tools',
      className: 'bpmn-icon-space-tool',
      title: translate('Space tool'),
      action: {
        click: function() { spaceTool.activateSelection(event); }
      }
    },
    'global-connect-tool': {
      group: 'tools',
      className: 'bpmn-icon-connection-multi',
      title: translate('Connect elements'),
      action: {
        click: function() { globalConnect.toggle(event); }
      }
    },
    // Éléments BPMN enrichis
    'create.start-event': createAction('bpmn:StartEvent', 'event', 'bpmn-icon-start-event-none'),
    'create.intermediate-event': createAction('bpmn:IntermediateThrowEvent', 'event', 'bpmn-icon-intermediate-event-none'),
    'create.end-event': createAction('bpmn:EndEvent', 'event', 'bpmn-icon-end-event-none'),
    'create.exclusive-gateway': createAction('bpmn:ExclusiveGateway', 'gateway', 'bpmn-icon-gateway-xor'),
    'create.parallel-gateway': createAction('bpmn:ParallelGateway', 'gateway', 'bpmn-icon-gateway-parallel'),
    'create.task': createAction('bpmn:Task', 'activity', 'bpmn-icon-task'),
    'create.user-task': createAction('bpmn:UserTask', 'activity', 'bpmn-icon-user-task'),
    'create.service-task': createAction('bpmn:ServiceTask', 'activity', 'bpmn-icon-service-task'),
    'create.script-task': createAction('bpmn:ScriptTask', 'activity', 'bpmn-icon-script-task'),
    'create.subprocess-expanded': createAction('bpmn:SubProcess', 'activity', 'bpmn-icon-subprocess-expanded', null, { isExpanded: true }),
    'create.participant-expanded': createAction('bpmn:Participant', 'collaboration', 'bpmn-icon-participant', 'Create Pool/Participant', { isExpanded: true }),
    'create.data-object': createAction('bpmn:DataObjectReference', 'data', 'bpmn-icon-data-object'),
    'create.data-store': createAction('bpmn:DataStoreReference', 'data', 'bpmn-icon-data-store'),
    // Ajoutez-en autant que vous voulez !
  };
};