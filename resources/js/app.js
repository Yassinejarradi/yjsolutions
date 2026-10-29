import './bootstrap';
// Police de l'interface, servie avec l'application (aucun appel à un service externe).
import '@fontsource-variable/inter';

// Livewire embarque Alpine.js : on le démarre depuis le bundle ESM pour pouvoir
// ajouter des composants Alpine sans charger Alpine deux fois.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import registerDocumentEditor, { autosize } from './document-editor';
import registerConfirmDialog from './confirm-dialog';

window.autosize = autosize;
registerDocumentEditor(Alpine);
registerConfirmDialog(Alpine);

Livewire.start();
