/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import "./styles/app.scss";

// start the Stimulus application
import "./bootstrap";

document.addEventListener("DOMContentLoaded", () => {
    const panier_modal = document.querySelector(".panier-modal");
    const panier_opener = document.querySelector("#open-panier");

    if (panier_opener && panier_modal) {
        panier_opener.addEventListener("click", () => {
            console.log(panier_opener);
            panier_modal.classList.toggle("open");
        });
    }
});

document.addEventListener("DOMContentLoaded", () => {
    const panier_html = document.querySelector("#panier");
    const total_html = document.querySelector("#total_panier");
    if (panier_html && total_html) {
        const elems = panier_html.querySelectorAll(".article");
        total_html.innerHTML = setTotal(elems) + "€";

        elems.forEach((elem) => {
            elem.querySelector("input.quantity_input").addEventListener(
                "input",
                () => {
                    total_html.innerHTML = setTotal(elems) + "€";
                }
            );
        });
    }
});

function setTotal(elems) {
    let total = 0;

    elems.forEach((elem) => {
        let quantity = elem.querySelector(".quantity_input").value;
        let price = elem.querySelector(".price").innerHTML;
        total += parseInt(price) * parseInt(quantity);
    });

    return total;
}
