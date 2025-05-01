import { startStimulusApp } from '@symfony/stimulus-bridge';
import * as Turbo from '@hotwired/turbo';
Turbo.start();

// Lancer Stimulus
startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.js$/
));