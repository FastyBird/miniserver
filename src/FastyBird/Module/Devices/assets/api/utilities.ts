export const CONNECTOR_DOCUMENT_REG_EXP = '^(?<source>[a-z0-9.-]+)/connector/(?<type>[a-z0-9-]+)$';
export const CONNECTOR_PROPERTIES_DOCUMENT_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/connector/(?<type>[a-z0-9-]+)$';
export const CONNECTOR_CONTROLS_DOCUMENT_REG_EXP = '^(?<source>[a-z0-9.-]+)/control/connector$';

export const DEVICE_DOCUMENT_REG_EXP = '^(?<source>[a-z0-9.-]+)/device/(?<type>[a-z0-9-]+)$';
export const DEVICE_PROPERTIES_DOCUMENT_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/device/(?<type>[a-z0-9-]+)$';
export const DEVICE_CONTROLS_DOCUMENT_REG_EXP = '^(?<source>[a-z0-9.-]+)/control/device$';

export const CHANNEL_DOCUMENT_REG_EXP = '^(?<source>[a-z0-9.-]+)/channel/(?<type>[a-z0-9-]+)$';
export const CHANNEL_PROPERTIES_DOCUMENT_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/channel/(?<type>[a-z0-9-]+)$';
export const CHANNEL_CONTROLS_DOCUMENT_REG_EXP = '^(?<source>[a-z0-9.-]+)/control/channel$';

export const ANY_CONTROL_DOCUMENT_REG_EXP = '^(?<source>[a-z0-9.-]+)/control/(?<parent>[a-z0-9-]+)$';
export const ANY_PROPERTY_DOCUMENT_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/(?<parent>[a-z0-9-]+)/(?<type>[a-z0-9-]+)$';
