import '../scss/app.scss';

// loads the Bootstrap jQuery plugins
import 'bootstrap-sass/assets/javascripts/bootstrap/transition.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/alert.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/collapse.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/dropdown.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/modal.js';
import 'jquery'

// loads the code syntax highlighting library
import './highlight.js';

// Creates links to the Symfony documentation
import './doclinks.js';


function updateQuantity(e) {
    let t = e.target;
    let id = t.dataset.targetId;
    let quantity = t.value;

    const req = new Request('/basket/update', {
        method: 'POST',
        credentials: 'same-origin',
        body: JSON.stringify({
            id: id,
            quantity: quantity
        })
    });

    fetch(req)
    .then(response => {
        return response.json();
    })
    .then(json => {
        document.querySelector(`.basket-product__price[data-target-id="${id}"`)
            .innerHTML = `€ ${json.price}`;
        document.querySelector('.basket-checkout__total-price')
            .innerHTML = json.totalPrice;
    })
    .catch(error => {});
}

quantityElts = document.querySelectorAll('.basket-product__quantity');
quantityElts.forEach (quantityElt => {
    quantityElt.addEventListener('change', updateQuantity);
});
