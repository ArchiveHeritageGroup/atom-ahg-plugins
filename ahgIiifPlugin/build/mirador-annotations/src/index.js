// Mirador 3.4.3 with the mirador-annotations editor and the AHG storage adapter.
// Same window.Mirador API as the stock bundle, plus Mirador.annotationPlugins and
// Mirador.AhgAnnotationAdapter (catalog #294).
import Mirador from 'mirador/dist/es/src/index';
import annotationPlugins from 'mirador-annotations';
import AhgAnnotationAdapter from './AhgAnnotationAdapter';

export default { ...Mirador, annotationPlugins, AhgAnnotationAdapter };
