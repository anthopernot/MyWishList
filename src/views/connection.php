
 <form id="form_message" method="POST" action="<?= $router->pathFor('connection') ?>">
     <div class="modal-body">
         <div class="form-group">
             <label for="inputNick">Entrez votre pseudo</label>
             <input type="text" name="nick" class="form-control" id="inputNick" placeholder="Xavier">
         </div>
         <div class="form-group">
             <label for="inputPassword">Entrez votre mot de passe</label>
             <input type="text" name="password" class="form-control" id="inputPassword">
         </div>
     </div>
     <div class="modal-footer">
         <button type="button" class="btn btn-secondary" data-dismiss="modal">Retour</button>
         <button type="submit" class="btn btn-primary">Se connecter</button>
     </div>
 </form>
