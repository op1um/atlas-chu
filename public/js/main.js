$(document).ready(function() {
    var nbUsPreviouslySelected = 0;
    $.fn.dataTable.moment('YYYY-MM-DD HH:mm:ss');
    $("#btnDeselectAll").click(function(){
      $.ui.fancytree.getTree("#tree").selectAll(false);
      return false;
    });
    $("#tree").fancytree({
      selectMode: 3,
      source: {
          url: "/loadFacilities",
          cache: false
        },
      lazyLoad: function(event, data){
          var node = data.node;
          data.result = {
            url: "/loadBuildingsAndLocations",
            type: "POST",
            dataType: "json",
            data: {
              "mode" : "children",
              "parent" : node.key,
              "nodeType" : node.data.nodeLevel
            },
          cache: false,
          async: true,
          };
      },
      select: function(event, data) {
        const maxUs = 5;
        // Get the list of all selected nodes, and convert to a key array:
        var selKeys = $.map(data.tree.getSelectedNodes(), function(node){
          return node.key;
        });
        // Get the list of all selected nodes, and convert to a key array:
        var selKeysLevel = $.map(data.tree.getSelectedNodes(), function(node){
          return node.data.nodeLevel;
        });
        var nbUsSelected = 0;

        for(var i = 0; i < selKeysLevel.length; ++i){
            if(selKeysLevel[i] == 'location')
              nbUsSelected++;
        }
        if(nbUsSelected == maxUs + 1 && nbUsSelected > nbUsPreviouslySelected){
          Swal.fire(
              'Attention',
              'Par soucis de performances, veuillez, svp, limiter le nombre d\'unités de soins sélectionnées à <u><b>'+maxUs+'</b></u> maximum.',
              'warning'
          );
        }
        nbUsPreviouslySelected = nbUsSelected;
      },
    });
    $tree = $.ui.fancytree.getTree("#tree");
    var node1 = $tree.getNodeByKey('10324606');
    node1.setSelected(true);
    $('#btnSubmit').click( function(){
      // Get the list of all selected nodes, and convert to a key array:
      var selKeys = $.map($.ui.fancytree.getTree("#tree").getSelectedNodes(), function(node){
        return node.key;
      });
      var selNames = $.map($.ui.fancytree.getTree("#tree").getSelectedNodes(), function(node){
        return node.title;
      });
      var idList = $('input[name=radioParamed]:checked').val() || '';
      var userId = $('#userId').val();
      if(selKeys.length == 0 ){
        Swal.fire(
            'Attention',
            'Veuillez choisir au moins une unité de soins afin de lancer une recherche.',
            'warning'
        );
      }
      if(typeof idList === "undefined"){
        Swal.fire(
            'Attention',
            'Veuillez choisir une liste paramédicale afin de lancer une recherche.',
            'warning'
        );
      }
      if(typeof userId === "undefined"){
        Swal.fire(
            'Une erreur est survenue',
            'Veuillez contacter le support utilisateur.',
            'error'
        );
      }
      if(selKeys.length > 0 && typeof idList !== "undefined" && typeof userId !== "undefined") {
        $('#initInfo').hide();
        $('#logo_chu').hide();
        $('#pageTitle').show();
        $('#person').show();
        $('#hosp_logo').addClass("pointer");
        var selText = '';
        selText = 'US séléctionnée(s) : <br/><br/><ul id="selectedUfs">';
        selNames.forEach(function (name, index) {
          selText = selText + '<li>' + name + '</li>';
        });
        selText = selText + '</ul>';
        $('#hosp_logo').attr('data-original-title', selText);
        $('[data-toggle="tooltip"]').tooltip();
        $('#person').DataTable( {
          ajax: {
            url:'/loadParamedList',
            type: "POST",
            dataType: "json",
            data: {
                "nurseUnits": selKeys,
                "listeParamed": idList,
                "userId" : userId
            },
            async: true
          },
          columns: [
              { data: 'ALIAS' },
              { data: 'NAME_FULL_FORMATTED' },
              { "data": 'BIRTH_DT_TM',
                "render": function(data, type, full)
                {
                    if (type == 'display')
                        return moment(data).format('DD/MM/YYYY');
                    else
                        return moment(data).format('YYYY-MM-DD');
                }
              },
              { data: 'LOC_NURSE_UNIT_DISPLAY' },
              { data: 'LOC_ROOM_DISPLAY' },
              { data: 'LOC_BED_DISPLAY' },
              { data: 'CATALOG_DISPLAY' },
              { "data": 'DATE_PRESC',
              "render": function(data, type, full)
              {
                  if (type == 'display')
                      return moment(data).format('DD/MM/YYYY HH:mm');
                  else
                      return moment(data).format('YYYY-MM-DD HH:mm:ss');
              }
            },
              { data: 'LAST_UPDATER' }
          ],
          "fnDrawCallback": function (oSettings, json){
            if(typeof oSettings.json !== "undefined"){
              var pageTitle = oSettings.json.pageTitle;
              $('#pageTitle').html(pageTitle);
            }
            
          },
          createdRow: function( row, data, dataIndex ) {
            if(typeof data !== "undefined") {
              $(row).attr("data-personId", data.PERSON_ID);
              $(row).attr("data-encntrId", data.ENCNTR_ID);
              $(row).addClass("tr-patient");
            }
          },
          destroy: true,
          lengthMenu: [[20, 40, 60, -1], [20, 40, 60, 'Tout']],
          language: {
              lengthMenu: 'Afficher _MENU_ patients par page',
              zeroRecords: 'Aucun élément ne correspond aux critères de recherche.',
              info: 'Page _PAGE_ sur _PAGES_',
              infoEmpty: '',
              infoFiltered: '(filtré sur _MAX_ patients)',
              loadingRecords: 'Chargement...',
              processing:     'Chargement...',
              search:         'Recherche :',
              paginate: {
                  first:      'Première',
                  last:       'Dernière',
                  next:       'Suivant',
                  previous:   'Précédent'
              },
              aria: {
                  sortAscending:  ': Cliquer pour trier par ordre croissant',
                  sortDescending: ': Cliquer pour trier par ordre décroissant'
              },
          }
      });
      }
    });

    $('body').delegate('.tr-patient', 'dblclick', function() {
        if(typeof $(this).attr("data-personId") !== "undefined" && typeof $(this).attr("data-encntrId") !== "undefined") {
          var personId = $(this).attr("data-personId");
          var encntrId = $(this).attr("data-encntrId");
          window.location.href = 'javascript:APPLINK(0,"$APP_APPNAME$","/PERSONID='+personId+' /ENCNTRID='+encntrId+' /FIRSTTAB=^Dossier patient^")';
        }
    });
});