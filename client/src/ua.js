const UserAgent = () => {
    if (typeof process !== 'undefined' ){
        return 'client_APP_Windows';
    }else if (typeof android !== 'undefined' ){
        return 'client_APP_Android';
    }else{
        return 'client_APP_Web';
    }
}
module.exports = UserAgent;