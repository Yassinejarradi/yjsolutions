import './bootstrap';

// Livewire 3 embarque Alpine.js : on le démarre depuis le bundle ESM pour pouvoir
// ajouter des plugins Alpine (ex. glisser-déposer des lignes) sans charger Alpine deux fois.
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

Livewire.start();
