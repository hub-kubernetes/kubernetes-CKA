 <!DOCTYPE html>  
 <!-- index.php !-->  
 <html>  
      <head>  
           <title>Pod Demo -  PHP & Mysql pod connectivity </title>  
           <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />  
           <script src="http://ajax.googleapis.com/ajax/libs/angularjs/1.4.8/angular.min.js"></script>  
      </head>  
      <body>  
           <br /><br />  
           <div class="container" style="width:500px;">  
                <div ng-app="myapp" ng-controller="usercontroller" ng-init="displayData()">  
                     <table class="table table-bordered">  
                          <tr>  
                               <th>First Name</th>  
                               <th>Last Name</th>  
                          </tr>  
                          <tr ng-repeat="x in names">  
                               <td>{{x.first_name}}</td>  
                               <td>{{x.last_name}}</td>  
                          </tr>  
                     </table>  
                </div>  
           </div>  
      </body>  
 </html>  
 <script>  
 var app = angular.module("myapp",[]);  
 app.controller("usercontroller", function($scope, $http){  
      $scope.insertData = function(){  
           $http.post(  
                "insert.php",  
                {'firstname':$scope.firstname, 'lastname':$scope.lastname}  
           ).success(function(data){  
                alert(data);  
                $scope.firstname = null;  
                $scope.lastname = null;  
                $scope.displayData();  
           });  
      }  
      $scope.displayData = function(){  
           $http.get("select.php")  
           .success(function(data){  
                $scope.names = data;  
           });  
      }  
 });  
 </script> 
