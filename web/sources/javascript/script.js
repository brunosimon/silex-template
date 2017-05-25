// Set up
const $ajaxLinks     = document.querySelectorAll('a.ajax')
const $ajaxContainer = document.querySelector('.ajax-container')
const $body          = document.body
const $title         = document.querySelector('title')

let ajaxInstance   = null
let ajaxUrl        = null

// Load
function load()
{
    // Ajax already running
    if(ajaxInstance)
    {
        return
    }

    // Hide container
    $ajaxContainer.classList.add('hidden')
    const hidePromise = new Promise(resolve => window.setTimeout(resolve, 500))

    // Call
    var headers = new Headers()
    headers.append('Accept', 'application/json')

    const fetchPromise = fetch(ajaxUrl, { headers: headers })
        .then((response) =>
        {
            return response.json()
        })


    const readyPromise = Promise.all([hidePromise, fetchPromise])
        .then((values) =>
        {
            const result = values[1]

            ajaxInstance = null

            // Update HTML
            $ajaxContainer.innerHTML = result.html

            // Update <title>
            $title.innerText = result.title

            // Update <body> classes
            $body.classList.remove($body.dataset.routename)
            $body.classList.add(result.route_name)
            $body.dataset.routename = result.route_name

            // Show container
            $ajaxContainer.classList.remove('hidden')
        })
}

// On ajax links click
for(let $ajaxLink of $ajaxLinks)
{
    $ajaxLink.addEventListener('click', function(event)
    {
        event.preventDefault()

        // Set ajax url
        ajaxUrl = $ajaxLink.getAttribute('href')

        // Load
        load()

        // Prevent default
        return false
    })
}
