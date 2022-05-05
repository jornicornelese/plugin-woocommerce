(function (document, Biller) {
    document.addEventListener("DOMContentLoaded", function () {
        let page = 0;
        let endpointUrl = document.getElementById('endpoint-url');
        if (!endpointUrl) {
            return;
        }

        let url = endpointUrl.value;

        document.getElementById('nextPage').addEventListener('click', nextPage);
        document.getElementById('previousPage').addEventListener('click', previousPage);
        Biller.Ajax.post(url, {'page': page}, function (response) {
            setData(response);
        }, 'json', true);

        function nextPage() {
            page++;
            Biller.Ajax.post(url, {'page': page}, function (response) {
                if(response.length === 0) {
                    page --;
                    return;
                }

                setData(response);
            }, 'json', true);
        }

        function previousPage() {
            if(page > 0) {
                page--;
            }
            Biller.Ajax.post(url, {'page': page}, function (response) {
                setData(response);
            }, 'json', true);
        }
        function setData(response) {
            let data = "";
            if(response.length === 0 && page === 0){
                data = "<tr class=''><p class='no-data'>No data</p></tr>"
            }
            for (let i = 0; i < response.length; i++) {
                data += "<tr class=''>";
                data += "<td class='notification-col'>" + response[i].id + "</td>"
                data += "<td class='notification-col'>" + response[i].date + "</td>";
                if(response[i].severity === 0) {
                    data += " <td style='text-align: center'><button type='button' class='info-btn'/>Info</td>";
                }
                if(response[i].severity === 1) {
                    data += " <td style='text-align: center'><button type='button' class='warning-btn'/>Warning</td>";
                }
                if(response[i].severity === 2) {
                    data += " <td style='text-align: center'><button type='button' class='error-btn'/>Error</td>";
                }
                data += "<td class='notification-col'>" + '#' + response[i].order + "</td>";
                data += " <td class='notification-col'>" + response[i].message + "</td>";
                data += "<td class='notification-col'>" + response[i].description +"</td>";
                data += " </tr>"
            }
            document.getElementById('table').innerHTML = data;
        }
    });
})(document, Biller);
